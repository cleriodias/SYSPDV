<?php

namespace App\Support;

use App\Models\Unidade;

class ActiveUnitSessionData
{
    public static function fromUnit(Unidade $unit): array
    {
        return [
            'id' => (int) $unit->tb2_id,
            'name' => self::displayName($unit),
            'address' => $unit->tb2_endereco,
            'cnpj' => $unit->tb2_cnpj,
        ];
    }

    public static function displayName(Unidade $unit): string
    {
        $unitType = mb_strtolower((string) ($unit->tb2_tipo ?? 'filial'));

        if ($unitType === 'matriz') {
            $matrixName = trim((string) ($unit->matriz?->nome ?? ''));

            if ($matrixName !== '') {
                return $matrixName;
            }
        }

        return (string) $unit->tb2_nome;
    }
}
