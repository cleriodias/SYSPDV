<?php

namespace App\Support;

use App\Models\Produto;
use App\Models\Unidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductQuickLookupCache
{
    private const CACHE_VERSION = 'v2';

    private const CACHE_TTL_MINUTES = 480;

    public function forRequest(Request $request): array
    {
        $unitId = $this->resolveActiveUnitId($request);

        if ($unitId <= 0) {
            return [];
        }

        return $this->forUnit($unitId);
    }

    public function forUnit(int $unitId): array
    {
        return Cache::remember(
            $this->cacheKey($unitId),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => $this->buildForUnit($unitId)
        );
    }

    public function rememberProductForRequest(Produto $product, Request $request): void
    {
        $unitId = $this->resolveActiveUnitId($request);

        if ($unitId <= 0 || (int) $product->tb1_status !== 1) {
            return;
        }

        $this->storeProductForUnit($unitId, $product);
    }

    public function syncProductForMatrix(Produto $product): void
    {
        $matrixId = (int) ($product->matriz_id ?? 0);

        if ($matrixId <= 0) {
            return;
        }

        Unidade::query()
            ->where('matriz_id', $matrixId)
            ->pluck('tb2_id')
            ->each(function ($unitId) use ($product): void {
                $resolvedUnitId = (int) $unitId;

                if ((int) $product->tb1_status === 1) {
                    $this->storeProductForUnit($resolvedUnitId, $product);
                    return;
                }

                $this->removeProductForUnit($resolvedUnitId, $product);
            });
    }

    public function productPayload(Produto $product): array
    {
        return [
            'tb1_id' => (int) $product->tb1_id,
            'produto_id' => (int) ($product->produto_id ?? $product->tb1_id),
            'tb1_nome' => (string) $product->tb1_nome,
            'tb1_codbar' => (string) $product->tb1_codbar,
            'tb1_vlr_custo' => (float) $product->tb1_vlr_custo,
            'tb1_vlr_venda' => (float) $product->tb1_vlr_venda,
            'tb1_tipo' => (int) $product->tb1_tipo,
            'tb1_qtd' => (int) ($product->tb1_qtd ?? 0),
            'tb1_status' => (int) $product->tb1_status,
            'tb1_vr_credit' => (bool) $product->tb1_vr_credit,
        ];
    }

    public function limit(): ?int
    {
        return null;
    }

    public function ttlHours(): int
    {
        return (int) (self::CACHE_TTL_MINUTES / 60);
    }

    private function buildForUnit(int $unitId): array
    {
        $matrixId = (int) (Unidade::query()
            ->where('tb2_id', $unitId)
            ->value('matriz_id') ?? 0);

        if ($matrixId <= 0) {
            return [];
        }

        return Produto::query()
            ->forMatrix($matrixId)
            ->where('tb1_status', 1)
            ->orderBy('tb1_nome')
            ->orderBy('produto_id')
            ->get([
                'tb1_id',
                'produto_id',
                'tb1_nome',
                'tb1_codbar',
                'tb1_vlr_custo',
                'tb1_vlr_venda',
                'tb1_tipo',
                'tb1_qtd',
                'tb1_status',
                'tb1_vr_credit',
            ])
            ->values()
            ->map(fn (Produto $product) => $this->productPayload($product))
            ->all();
    }

    private function resolveActiveUnitId(Request $request): int
    {
        $activeUnit = $request->session()->get('active_unit');
        $unitId = 0;

        if (is_array($activeUnit)) {
            $unitId = (int) ($activeUnit['id'] ?? $activeUnit['tb2_id'] ?? 0);
        } elseif (is_object($activeUnit)) {
            $unitId = (int) ($activeUnit->id ?? $activeUnit->tb2_id ?? 0);
        }

        if ($unitId <= 0) {
            $unitId = (int) ($request->user()?->tb2_id ?? 0);
        }

        return $unitId;
    }

    private function cacheKey(int $unitId): string
    {
        return sprintf('dashboard:quick-products:%s:unit:%d', self::CACHE_VERSION, $unitId);
    }

    private function storeProductForUnit(int $unitId, Produto $product): void
    {
        if ($unitId <= 0) {
            return;
        }

        $key = $this->cacheKey($unitId);
        $productPayload = $this->productPayload($product);
        $currentProducts = Cache::get($key, []);

        if (! is_array($currentProducts)) {
            $currentProducts = [];
        }

        $nextProducts = array_values(array_filter(
            $currentProducts,
            fn ($cachedProduct) => (int) ($cachedProduct['tb1_id'] ?? 0) !== (int) $product->tb1_id
        ));

        array_unshift($nextProducts, $productPayload);

        Cache::put($key, $nextProducts, now()->addMinutes(self::CACHE_TTL_MINUTES));
    }

    private function removeProductForUnit(int $unitId, Produto $product): void
    {
        if ($unitId <= 0) {
            return;
        }

        $key = $this->cacheKey($unitId);
        $currentProducts = Cache::get($key, []);

        if (! is_array($currentProducts)) {
            $currentProducts = [];
        }

        $nextProducts = array_values(array_filter(
            $currentProducts,
            fn ($cachedProduct) => (int) ($cachedProduct['tb1_id'] ?? 0) !== (int) $product->tb1_id
        ));

        if ($nextProducts === []) {
            Cache::forget($key);
            return;
        }

        Cache::put($key, $nextProducts, now()->addMinutes(self::CACHE_TTL_MINUTES));
    }
}
