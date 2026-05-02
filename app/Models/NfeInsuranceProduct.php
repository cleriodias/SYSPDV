<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NfeInsuranceProduct extends Model
{
    use HasFactory;

    protected $table = 'tb30_nfe_produtos_seguro';

    protected $primaryKey = 'tb30_id';

    protected $fillable = [
        'matriz_id',
        'tb2_id',
        'tb30_codigo',
        'tb30_nome',
        'tb30_seguradora',
        'tb30_ramo',
        'tb30_modalidade',
        'tb30_tipo_contratacao',
        'tb30_periodicidade',
        'tb30_cfop',
        'tb30_ncm',
        'tb30_unidade_padrao',
        'tb30_premio_base',
        'tb30_comissao_percentual',
        'tb30_regras',
        'tb30_status',
    ];

    protected $casts = [
        'matriz_id' => 'integer',
        'tb2_id' => 'integer',
        'tb30_premio_base' => 'float',
        'tb30_comissao_percentual' => 'float',
        'tb30_status' => 'integer',
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
