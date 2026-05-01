<?php

namespace App\Support;

use App\Models\Unidade;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SystemChatUserResolver
{
    public const CURRENT_ENV_SYSTEM_USER_ID = 2;

    public const SYSTEM_EMAIL = 'sistema.chat@pec.local';

    public function resolve(?int $activeUnitId = null): User
    {
        $systemUser = $this->find();

        if ($systemUser) {
            return $this->syncUnits($systemUser, $activeUnitId);
        }

        $activeUnitIds = $this->activeUnitIds();
        $primaryUnitId = $activeUnitId && $activeUnitId > 0
            ? $activeUnitId
            : (int) ($activeUnitIds->first() ?? 0);

        $systemUser = User::query()->firstOrCreate(
            ['email' => self::SYSTEM_EMAIL],
            [
                'name' => 'Sistema',
                'password' => Str::random(32),
                'funcao' => 1,
                'funcao_original' => 1,
                'hr_ini' => '00:00',
                'hr_fim' => '23:59',
                'salario' => 0,
                'vr_cred' => 0,
                'tb2_id' => $primaryUnitId > 0 ? $primaryUnitId : null,
                'cod_acesso' => Str::upper(Str::random(6)),
            ]
        );

        return $this->syncUnits($systemUser, $activeUnitId, $activeUnitIds);
    }

    public function find(): ?User
    {
        $legacySystemUser = User::query()->find(self::CURRENT_ENV_SYSTEM_USER_ID);

        if ($legacySystemUser) {
            return $legacySystemUser;
        }

        return User::query()->where('email', self::SYSTEM_EMAIL)->first();
    }

    public function systemUserId(): ?int
    {
        return $this->find()?->id;
    }

    public function isSystemUserId(?int $userId): bool
    {
        if (! $userId || $userId <= 0) {
            return false;
        }

        $resolvedId = $this->systemUserId();

        return $resolvedId !== null && (int) $resolvedId === (int) $userId;
    }

    public function displayName(?User $user = null): string
    {
        if ($user && ! $this->isSystemUserId((int) $user->id)) {
            return (string) ($user->name ?? '---');
        }

        return 'Sistema';
    }

    private function syncUnits(User $systemUser, ?int $activeUnitId = null, ?Collection $activeUnitIds = null): User
    {
        $activeUnitIds ??= $this->activeUnitIds();
        $nextPrimaryUnitId = $activeUnitId && $activeUnitId > 0
            ? $activeUnitId
            : (int) ($activeUnitIds->first() ?? ($systemUser->tb2_id ?? 0));

        if ($nextPrimaryUnitId > 0 && (int) ($systemUser->tb2_id ?? 0) !== $nextPrimaryUnitId) {
            $systemUser->forceFill(['tb2_id' => $nextPrimaryUnitId])->save();
        }

        if ($activeUnitIds->isNotEmpty()) {
            $systemUser->units()->syncWithoutDetaching($activeUnitIds->all());
        }

        return $systemUser->fresh(['units']) ?? $systemUser;
    }

    private function activeUnitIds(): Collection
    {
        return Unidade::active()
            ->orderBy('tb2_id')
            ->pluck('tb2_id')
            ->map(fn ($value) => (int) $value)
            ->values();
    }
}
