<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aplicacao extends Model
{
    use HasFactory;

    public const PADARIA_NFE = 1;
    public const PADARIA = 2;
    public const NFE = 3;

    protected $table = 'tb28_aplicacoes';

    protected $primaryKey = 'tb28_id';

    protected $fillable = [
        'tb28_nome',
        'tb28_slug',
        'tb28_rota_inicial',
    ];

    public static function defaultInitialRoute(int $applicationId): string
    {
        return match ($applicationId) {
            self::PADARIA_NFE => 'dashboard',
            self::PADARIA => 'padaria',
            self::NFE => 'nfe?unit_id={unit_id}',
            default => 'dashboard',
        };
    }

    public function matrizes(): HasMany
    {
        return $this->hasMany(Matriz::class, 'tb28_id', 'tb28_id');
    }
}
