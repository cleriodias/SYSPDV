<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NfeLaunch extends Model
{
    use HasFactory;

    protected $table = 'tb29_nfe_lancamentos';

    protected $primaryKey = 'tb29_id';

    protected $fillable = [
        'tb29_numero',
        'tb2_id',
        'matriz_id',
        'user_id',
        'tb29_status',
        'tb29_tipo_operacao',
        'tb29_finalidade',
        'tb29_data_lancamento',
        'tb29_data_emissao',
        'tb29_data_competencia',
        'tb29_destinatario',
        'tb29_comercial',
        'tb29_itens',
        'tb29_pagamento',
        'tb29_totais',
        'tb29_pendencias',
        'tb29_observacoes',
        'tb29_historico',
    ];

    protected $casts = [
        'tb2_id' => 'integer',
        'matriz_id' => 'integer',
        'user_id' => 'integer',
        'tb29_data_lancamento' => 'date',
        'tb29_data_emissao' => 'date',
        'tb29_data_competencia' => 'date',
        'tb29_destinatario' => 'array',
        'tb29_comercial' => 'array',
        'tb29_itens' => 'array',
        'tb29_pagamento' => 'array',
        'tb29_totais' => 'array',
        'tb29_pendencias' => 'array',
        'tb29_historico' => 'array',
    ];

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class, 'tb2_id', 'tb2_id');
    }

    public function matriz(): BelongsTo
    {
        return $this->belongsTo(Matriz::class, 'matriz_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
