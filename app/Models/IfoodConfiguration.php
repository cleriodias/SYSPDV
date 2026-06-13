<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IfoodConfiguration extends Model
{
    use HasFactory;

    protected $table = 'tb33_ifood_configuracoes';

    protected $primaryKey = 'tb33_id';

    protected $fillable = [
        'tb2_id',
        'tb33_ativo',
        'tb33_ambiente',
        'tb33_nome_loja',
        'tb33_merchant_id',
        'tb33_client_id',
        'tb33_client_secret',
        'tb33_authorization_code',
        'tb33_webhook_token',
        'tb33_observacoes',
    ];

    protected $casts = [
        'tb33_ativo' => 'boolean',
        'tb33_client_secret' => 'encrypted',
        'tb33_authorization_code' => 'encrypted',
    ];

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class, 'tb2_id', 'tb2_id');
    }
}
