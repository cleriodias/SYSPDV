<?php

namespace App\Support;

use App\Models\BillingPlanSetting;
use App\Models\CobrancaMensal;
use App\Models\Matriz;
use App\Models\Unidade;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RecurringBillingService
{
    public function syncForDashboard(Collection $matrizes, Carbon $referenceDate, array $planSettings = []): void
    {
        $defaults = $this->resolvePlanSettings($planSettings);

        foreach ($matrizes as $matriz) {
            if (! ($matriz instanceof Matriz)) {
                continue;
            }

            $this->syncMatrix($matriz, $referenceDate, $defaults['matrix_monthly_price']);

            $units = $matriz->relationLoaded('units')
                ? $matriz->units
                : $matriz->units()->get();

            foreach ($units as $unit) {
                if (! ($unit instanceof Unidade) || (string) ($unit->tb2_tipo ?? 'filial') !== CobrancaMensal::TIPO_FILIAL) {
                    continue;
                }

                $this->syncUnit($unit, $referenceDate, $defaults['branch_monthly_price']);
            }
        }
    }

    public function syncMatrix(Matriz $matriz, Carbon $referenceDate, ?float $fallbackAmount = null): void
    {
        $this->syncEntity(
            matrizId: (int) $matriz->id,
            referenceType: CobrancaMensal::TIPO_MATRIZ,
            referenceId: (int) $matriz->id,
            unitId: null,
            contractedAt: $matriz->plano_contratado_em ?? $matriz->created_at,
            amount: $matriz->plano_mensal_valor ?? $fallbackAmount,
            referenceDate: $referenceDate,
        );
    }

    public function syncUnit(Unidade $unit, Carbon $referenceDate, ?float $fallbackAmount = null): void
    {
        $this->syncEntity(
            matrizId: (int) ($unit->matriz_id ?? 0),
            referenceType: CobrancaMensal::TIPO_FILIAL,
            referenceId: (int) $unit->tb2_id,
            unitId: (int) $unit->tb2_id,
            contractedAt: $unit->plano_contratado_em ?? $unit->created_at,
            amount: $unit->plano_mensal_valor ?? $fallbackAmount,
            referenceDate: $referenceDate,
        );
    }

    public function buildDashboardSummaries(Collection $matrizes, Carbon $referenceDate): array
    {
        $matrixIds = $matrizes
            ->filter(fn ($item) => $item instanceof Matriz)
            ->map(fn (Matriz $matriz) => (int) $matriz->id)
            ->filter(fn (int $id) => $id > 0)
            ->values();

        $unitIds = $matrizes
            ->filter(fn ($item) => $item instanceof Matriz)
            ->flatMap(function (Matriz $matriz) {
                $units = $matriz->relationLoaded('units')
                    ? $matriz->units
                    : $matriz->units()->get();

                return $units
                    ->filter(fn ($unit) => $unit instanceof Unidade && (string) ($unit->tb2_tipo ?? 'filial') === CobrancaMensal::TIPO_FILIAL)
                    ->map(fn (Unidade $unit) => (int) $unit->tb2_id);
            })
            ->filter(fn (int $id) => $id > 0)
            ->values();

        if ($matrixIds->isEmpty() && $unitIds->isEmpty()) {
            return [
                CobrancaMensal::TIPO_MATRIZ => [],
                CobrancaMensal::TIPO_FILIAL => [],
            ];
        }

        $currentCompetence = $referenceDate->copy()->startOfMonth()->toDateString();

        $charges = CobrancaMensal::query()
            ->where('competencia', '<=', $currentCompetence)
            ->where(function ($query) use ($matrixIds, $unitIds) {
                if ($matrixIds->isNotEmpty()) {
                    $query->where(function ($subQuery) use ($matrixIds) {
                        $subQuery
                            ->where('referencia_tipo', CobrancaMensal::TIPO_MATRIZ)
                            ->whereIn('referencia_id', $matrixIds->all());
                    });
                }

                if ($unitIds->isNotEmpty()) {
                    $method = $matrixIds->isNotEmpty() ? 'orWhere' : 'where';

                    $query->{$method}(function ($subQuery) use ($unitIds) {
                        $subQuery
                            ->where('referencia_tipo', CobrancaMensal::TIPO_FILIAL)
                            ->whereIn('referencia_id', $unitIds->all());
                    });
                }
            })
            ->orderBy('competencia')
            ->orderBy('data_vencimento')
            ->get()
            ->groupBy(fn (CobrancaMensal $charge) => $charge->referencia_tipo . ':' . $charge->referencia_id);

        $summaries = [
            CobrancaMensal::TIPO_MATRIZ => [],
            CobrancaMensal::TIPO_FILIAL => [],
        ];

        foreach ($charges as $key => $group) {
            [$referenceType, $referenceId] = explode(':', (string) $key, 2);
            $summaries[$referenceType][(int) $referenceId] = $this->buildSummaryForCharges(
                $group,
                $referenceDate,
                $currentCompetence,
            );
        }

        return $summaries;
    }

    public function toggleMatrixPayment(Matriz $matriz, Carbon $referenceDate, ?float $fallbackAmount = null): bool
    {
        $this->syncMatrix($matriz, $referenceDate, $fallbackAmount);

        $markedAsPaid = $this->toggleEntityCharges(
            CobrancaMensal::TIPO_MATRIZ,
            (int) $matriz->id,
            $referenceDate,
        );

        $this->syncLegacyMatrixFlag($matriz, $markedAsPaid);

        return $markedAsPaid;
    }

    public function toggleUnitPayment(Unidade $unit, Carbon $referenceDate, ?float $fallbackAmount = null): bool
    {
        $this->syncUnit($unit, $referenceDate, $fallbackAmount);

        $markedAsPaid = $this->toggleEntityCharges(
            CobrancaMensal::TIPO_FILIAL,
            (int) $unit->tb2_id,
            $referenceDate,
        );

        $unit->forceFill([
            'pagamento_ativo' => $markedAsPaid,
        ])->save();

        return $markedAsPaid;
    }

    private function syncEntity(
        int $matrizId,
        string $referenceType,
        int $referenceId,
        ?int $unitId,
        mixed $contractedAt,
        ?float $amount,
        Carbon $referenceDate
    ): void {
        if ($matrizId <= 0 || $referenceId <= 0 || $amount === null) {
            return;
        }

        $contractDate = $this->normalizeContractDate($contractedAt);

        if (! ($contractDate instanceof Carbon)) {
            return;
        }

        $currentOccurrence = $contractDate->copy();
        $todayCompetence = $referenceDate->copy()->startOfMonth();

        while ($currentOccurrence->copy()->startOfMonth()->lte($todayCompetence)) {
            $competence = $currentOccurrence->copy()->startOfMonth()->toDateString();

            $charge = CobrancaMensal::query()->firstOrNew([
                'referencia_tipo' => $referenceType,
                'referencia_id' => $referenceId,
                'competencia' => $competence,
            ]);

            $charge->fill([
                'matriz_id' => $matrizId,
                'tb2_id' => $unitId,
                'data_vencimento' => $currentOccurrence->toDateString(),
            ]);

            if (! $charge->exists || $charge->status_pagamento !== CobrancaMensal::STATUS_PAGO) {
                $charge->valor_cobrado = round((float) $amount, 2);
            }

            if (! $charge->exists) {
                $charge->status_pagamento = CobrancaMensal::STATUS_PENDENTE;
                $charge->pago_em = null;
            }

            if ($charge->isDirty()) {
                $charge->save();
            }

            $currentOccurrence = $this->nextMonthlyOccurrence($currentOccurrence, (int) $contractDate->day);
        }
    }

    private function buildSummaryForCharges(Collection $charges, Carbon $referenceDate, string $currentCompetence): array
    {
        $today = $referenceDate->copy()->startOfDay();
        $currentCharge = $charges->first(
            fn (CobrancaMensal $charge) => $charge->competencia?->toDateString() === $currentCompetence
        );

        $pendingCharges = $charges->filter(
            fn (CobrancaMensal $charge) => $charge->status_pagamento === CobrancaMensal::STATUS_PENDENTE
        );

        $pendingUpToCurrent = $pendingCharges->filter(
            fn (CobrancaMensal $charge) => $charge->competencia?->toDateString() <= $currentCompetence
        );

        $overdueCharges = $pendingUpToCurrent->filter(
            fn (CobrancaMensal $charge) => $charge->data_vencimento instanceof Carbon
                && $charge->data_vencimento->copy()->startOfDay()->lt($today)
        );

        $nextPendingCharge = $pendingUpToCurrent
            ->sortBy(fn (CobrancaMensal $charge) => $charge->data_vencimento?->toDateString() ?? '9999-12-31')
            ->first();

        $status = $this->resolveStatus($currentCharge, $overdueCharges->count(), $today);

        return [
            'status_key' => $status['key'],
            'status_label' => $status['label'],
            'pending_count' => $pendingUpToCurrent->count(),
            'pending_amount' => round((float) $pendingUpToCurrent->sum('valor_cobrado'), 2),
            'overdue_count' => $overdueCharges->count(),
            'overdue_amount' => round((float) $overdueCharges->sum('valor_cobrado'), 2),
            'current_charge_status' => $currentCharge?->status_pagamento,
            'current_charge_due_at' => $currentCharge?->data_vencimento?->toDateString(),
            'current_charge_paid_at' => $currentCharge?->pago_em?->toDateTimeString(),
            'current_charge_amount' => $currentCharge ? round((float) $currentCharge->valor_cobrado, 2) : 0,
            'current_charge_competence' => $currentCharge?->competencia?->toDateString(),
            'next_pending_due_at' => $nextPendingCharge?->data_vencimento?->toDateString(),
        ];
    }

    private function resolveStatus(?CobrancaMensal $currentCharge, int $overdueCount, Carbon $today): array
    {
        if ($overdueCount > 0) {
            return [
                'key' => 'atrasado',
                'label' => 'Atrasado',
            ];
        }

        if ($currentCharge?->status_pagamento === CobrancaMensal::STATUS_PAGO) {
            return [
                'key' => 'pago',
                'label' => 'Pago',
            ];
        }

        if (! $currentCharge || ! ($currentCharge->data_vencimento instanceof Carbon)) {
            return [
                'key' => 'pendente',
                'label' => 'Pendente',
            ];
        }

        $dueDate = $currentCharge->data_vencimento->copy()->startOfDay();

        if ($dueDate->isSameDay($today)) {
            return [
                'key' => 'vence_hoje',
                'label' => 'Vence hoje',
            ];
        }

        if ($dueDate->gt($today)) {
            return [
                'key' => 'a_vencer',
                'label' => 'A vencer',
            ];
        }

        return [
            'key' => 'pendente',
            'label' => 'Pendente',
        ];
    }

    private function toggleEntityCharges(string $referenceType, int $referenceId, Carbon $referenceDate): bool
    {
        $currentCompetence = $referenceDate->copy()->startOfMonth()->toDateString();
        $currentCharge = CobrancaMensal::query()
            ->where('referencia_tipo', $referenceType)
            ->where('referencia_id', $referenceId)
            ->where('competencia', $currentCompetence)
            ->first();

        $pendingCharges = CobrancaMensal::query()
            ->where('referencia_tipo', $referenceType)
            ->where('referencia_id', $referenceId)
            ->where('competencia', '<=', $currentCompetence)
            ->where('status_pagamento', CobrancaMensal::STATUS_PENDENTE)
            ->get();

        if ($pendingCharges->isNotEmpty()) {
            CobrancaMensal::query()
                ->whereIn('id', $pendingCharges->pluck('id')->all())
                ->update([
                    'status_pagamento' => CobrancaMensal::STATUS_PAGO,
                    'pago_em' => now(),
                    'updated_at' => now(),
                ]);

            return true;
        }

        if (! ($currentCharge instanceof CobrancaMensal)) {
            return false;
        }

        $currentCharge->forceFill([
            'status_pagamento' => CobrancaMensal::STATUS_PENDENTE,
            'pago_em' => null,
        ])->save();

        return false;
    }

    private function syncLegacyMatrixFlag(Matriz $matriz, bool $status): void
    {
        $matriz->forceFill([
            'pagamento_ativo' => $status,
        ])->save();

        $matriz->units()
            ->where('tb2_tipo', 'matriz')
            ->update([
                'pagamento_ativo' => $status,
            ]);
    }

    private function normalizeContractDate(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy()->startOfDay();
        }

        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function nextMonthlyOccurrence(Carbon $currentDate, int $targetDay): Carbon
    {
        $nextMonthStart = $currentDate->copy()->addMonthNoOverflow()->startOfMonth();
        $safeDay = min($targetDay, $nextMonthStart->daysInMonth);

        return $nextMonthStart->copy()->day($safeDay)->startOfDay();
    }

    private function resolvePlanSettings(array $planSettings): array
    {
        if ($planSettings !== []) {
            return [
                'matrix_monthly_price' => (float) ($planSettings['matrix_monthly_price'] ?? 250),
                'branch_monthly_price' => (float) ($planSettings['branch_monthly_price'] ?? 180),
            ];
        }

        $settings = BillingPlanSetting::query()->first();

        return [
            'matrix_monthly_price' => (float) ($settings->matrix_monthly_price ?? 250),
            'branch_monthly_price' => (float) ($settings->branch_monthly_price ?? 180),
        ];
    }
}
