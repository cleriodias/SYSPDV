<?php

namespace App\Http\Controllers;

use App\Models\IfoodConfiguration;
use App\Models\Unidade;
use App\Support\ManagementScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class IfoodConfigurationController extends Controller
{
    private const ENVIRONMENT_OPTIONS = ['homologacao', 'producao'];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->ensureAdmin($user);

        $units = ManagementScope::managedUnits($user, ['tb2_id', 'tb2_nome', 'tb2_cnpj', 'tb2_endereco'])
            ->map(fn (Unidade $unit) => [
                'id' => (int) $unit->tb2_id,
                'name' => (string) $unit->tb2_nome,
                'cnpj' => (string) ($unit->tb2_cnpj ?? ''),
                'endereco' => (string) ($unit->tb2_endereco ?? ''),
            ])
            ->values();

        $selectedUnitId = (int) $request->query('unit_id', 0);

        if ($selectedUnitId <= 0) {
            $selectedUnitId = (int) ($units->first()['id'] ?? 0);
        }

        if ($selectedUnitId > 0 && ! ManagementScope::canManageUnit($user, $selectedUnitId)) {
            abort(403, 'Acesso negado.');
        }

        $unit = $selectedUnitId > 0
            ? Unidade::query()->find($selectedUnitId)
            : null;

        $configuration = $selectedUnitId > 0
            ? IfoodConfiguration::query()->where('tb2_id', $selectedUnitId)->first()
            : null;

        return Inertia::render('Settings/IfoodConfig', [
            'units' => $units,
            'selectedUnitId' => $selectedUnitId > 0 ? $selectedUnitId : null,
            'unit' => $unit ? [
                'id' => (int) $unit->tb2_id,
                'name' => (string) $unit->tb2_nome,
                'cnpj' => (string) ($unit->tb2_cnpj ?? ''),
                'endereco' => (string) ($unit->tb2_endereco ?? ''),
            ] : null,
            'configuration' => $this->buildConfigurationPayload($configuration, $selectedUnitId),
            'environmentOptions' => collect(self::ENVIRONMENT_OPTIONS)
                ->map(fn (string $value) => [
                    'value' => $value,
                    'label' => $value === 'producao' ? 'Producao' : 'Homologacao',
                ])
                ->values(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->ensureAdmin($user);

        $data = $request->validate([
            'tb2_id' => ['required', 'integer', 'exists:tb2_unidades,tb2_id'],
            'tb33_ativo' => ['required', 'boolean'],
            'tb33_ambiente' => ['required', 'string', 'in:' . implode(',', self::ENVIRONMENT_OPTIONS)],
            'tb33_nome_loja' => ['nullable', 'string', 'max:120'],
            'tb33_merchant_id' => ['nullable', 'string', 'max:120'],
            'tb33_client_id' => ['nullable', 'string', 'max:120'],
            'tb33_client_secret' => ['nullable', 'string', 'max:4000'],
            'tb33_authorization_code' => ['nullable', 'string', 'max:4000'],
            'tb33_webhook_token' => ['nullable', 'string', 'max:120'],
            'tb33_observacoes' => ['nullable', 'string', 'max:5000'],
        ]);

        $unitId = (int) $data['tb2_id'];

        if (! ManagementScope::canManageUnit($user, $unitId)) {
            abort(403, 'Acesso negado.');
        }

        $configuration = IfoodConfiguration::query()->firstOrNew(['tb2_id' => $unitId]);
        $clientSecret = $this->nullableString($data['tb33_client_secret'] ?? null);
        $authorizationCode = $this->nullableString($data['tb33_authorization_code'] ?? null);
        $existingHasClientSecret = filled($configuration->tb33_client_secret);

        $validator = Validator::make($data, []);

        if ((bool) $data['tb33_ativo']) {
            if (! filled($this->nullableString($data['tb33_nome_loja'] ?? null))) {
                $validator->errors()->add('tb33_nome_loja', 'Informe o nome da loja no iFood para ativar a integracao.');
            }

            if (! filled($this->nullableString($data['tb33_merchant_id'] ?? null))) {
                $validator->errors()->add('tb33_merchant_id', 'Informe o Merchant ID da loja no iFood para ativar a integracao.');
            }

            if (! filled($this->nullableString($data['tb33_client_id'] ?? null))) {
                $validator->errors()->add('tb33_client_id', 'Informe o Client ID da integracao para ativar a funcao.');
            }

            if (! $existingHasClientSecret && ! filled($clientSecret)) {
                $validator->errors()->add('tb33_client_secret', 'Informe o Client Secret para ativar a funcao.');
            }
        }

        if ($validator->errors()->isNotEmpty()) {
            throw new ValidationException($validator);
        }

        $payload = [
            'tb33_ativo' => (bool) $data['tb33_ativo'],
            'tb33_ambiente' => (string) $data['tb33_ambiente'],
            'tb33_nome_loja' => $this->nullableString($data['tb33_nome_loja'] ?? null),
            'tb33_merchant_id' => $this->nullableString($data['tb33_merchant_id'] ?? null),
            'tb33_client_id' => $this->nullableString($data['tb33_client_id'] ?? null),
            'tb33_webhook_token' => $this->nullableString($data['tb33_webhook_token'] ?? null),
            'tb33_observacoes' => $this->nullableString($data['tb33_observacoes'] ?? null),
        ];

        if ($clientSecret !== null) {
            $payload['tb33_client_secret'] = $clientSecret;
        }

        if ($authorizationCode !== null) {
            $payload['tb33_authorization_code'] = $authorizationCode;
        }

        $configuration->fill($payload);
        $configuration->save();

        return redirect()
            ->route('settings.ifood', ['unit_id' => $unitId])
            ->with('success', 'Configuracao do iFood atualizada com sucesso.');
    }

    private function buildConfigurationPayload(?IfoodConfiguration $configuration, int $selectedUnitId): array
    {
        return [
            'tb2_id' => (int) ($configuration?->tb2_id ?? $selectedUnitId),
            'tb33_ativo' => (bool) ($configuration?->tb33_ativo ?? false),
            'tb33_ambiente' => (string) ($configuration?->tb33_ambiente ?? 'homologacao'),
            'tb33_nome_loja' => (string) ($configuration?->tb33_nome_loja ?? ''),
            'tb33_merchant_id' => (string) ($configuration?->tb33_merchant_id ?? ''),
            'tb33_client_id' => (string) ($configuration?->tb33_client_id ?? ''),
            'tb33_webhook_token' => (string) ($configuration?->tb33_webhook_token ?? ''),
            'tb33_observacoes' => (string) ($configuration?->tb33_observacoes ?? ''),
            'has_client_secret' => filled($configuration?->tb33_client_secret),
            'has_authorization_code' => filled($configuration?->tb33_authorization_code),
            'client_secret_mask' => filled($configuration?->tb33_client_secret)
                ? 'Configurado e protegido'
                : 'Nao configurado',
            'authorization_code_mask' => filled($configuration?->tb33_authorization_code)
                ? 'Configurado e protegido'
                : 'Nao configurado',
            'updated_at' => optional($configuration?->updated_at)->format('d/m/y H:i'),
        ];
    }

    private function ensureAdmin($user): void
    {
        if (! $user || ! ManagementScope::isAdmin($user)) {
            abort(403);
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
