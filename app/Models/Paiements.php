<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiements extends Model
{
    use HasFactory;
    protected $fillable = [
        'paiement_date',
        'user_id',
        'categorie_id',
        'nombre_de_code',
        'montant',
        'numero_payeur',
        'numero_client',
        'status',
        'transaction_id'
    ];

    protected $table = 'paiements';
    /**
     * Get the user that owns the Paiements
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the categorie that owns the Paiements
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class, 'categorie_id', 'id');
    }
}
