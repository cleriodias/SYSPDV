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
        return trim((string) $unit->tb2_nome) !== ''
            ? trim((string) $unit->tb2_nome)
            : ('Unidade #' . (int) ($unit->tb2_id ?? 0));
    }
}
