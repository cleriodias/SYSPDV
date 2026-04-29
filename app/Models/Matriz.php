<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Matriz extends Model
{
    use HasFactory;

    protected $table = 'matrizes';

    protected $fillable = [
        'nome',
        'slug',
        'cnpj',
        'tb28_id',
        'status',
        'plano_mensal_valor',
        'plano_contratado_em',
        'pagamento_ativo',
    ];

    protected $casts = [
        'tb28_id' => 'integer',
        'status' => 'integer',
        'plano_mensal_valor' => 'float',
        'plano_contratado_em' => 'datetime',
        'pagamento_ativo' => 'boolean',
    ];

    public function aplicacao(): BelongsTo
    {
        return $this->belongsTo(Aplicacao::class, 'tb28_id', 'tb28_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unidade::class, 'matriz_id', 'id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'matriz_id', 'id');
    }
}
