<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CobrancaMensal extends Model
{
    use HasFactory;

    public const STATUS_PENDENTE = 'pendente';
    public const STATUS_PAGO = 'pago';

    public const TIPO_MATRIZ = 'matriz';
    public const TIPO_FILIAL = 'filial';

    protected $table = 'tb32_cobrancas_mensais';

    protected $fillable = [
        'matriz_id',
        'tb2_id',
        'referencia_tipo',
        'referencia_id',
        'competencia',
        'data_vencimento',
        'valor_cobrado',
        'status_pagamento',
        'pago_em',
    ];

    protected $casts = [
        'matriz_id' => 'integer',
        'tb2_id' => 'integer',
        'referencia_id' => 'integer',
        'competencia' => 'date',
        'data_vencimento' => 'date',
        'valor_cobrado' => 'float',
        'pago_em' => 'datetime',
    ];

    public function matriz(): BelongsTo
    {
        return $this->belongsTo(Matriz::class, 'matriz_id');
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class, 'tb2_id', 'tb2_id');
    }
}
