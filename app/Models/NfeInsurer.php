<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NfeInsurer extends Model
{
    use HasFactory;

    protected $table = 'tb31_nfe_seguradoras';

    protected $primaryKey = 'tb31_id';

    protected $fillable = [
        'matriz_id',
        'tb31_nome_fantasia',
        'tb31_razao_social',
        'tb31_cnpj',
        'tb31_codigo_susep',
        'tb31_status',
        'tb31_usa_integracao',
        'tb31_codigo_externo_integracao',
        'tb31_observacoes_operacionais',
    ];

    protected $casts = [
        'matriz_id' => 'integer',
        'tb31_status' => 'integer',
        'tb31_usa_integracao' => 'boolean',
    ];

    public function matriz(): BelongsTo
    {
        return $this->belongsTo(Matriz::class, 'matriz_id');
    }

    public function insuranceProducts(): HasMany
    {
        return $this->hasMany(NfeInsuranceProduct::class, 'tb31_id', 'tb31_id');
    }
}
