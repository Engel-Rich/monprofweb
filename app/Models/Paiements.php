<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Paiements extends Model
{
    use HasFactory;

    public const ORIGIN_PURCHASE = 'PURCHASE';

    /** Code offert depuis la console d'administration, sans transaction. */
    public const ORIGIN_ADMIN_GRANT = 'ADMIN_GRANT';

    protected $fillable = [
        'paiement_date',
        'user_id',
        'categorie_id',
        'nombre_de_code',
        'montant',
        'numero_payeur',
        'numero_client',
        'status',
        'transaction_id',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
        'origin',
        'granted_by',
        'grant_reason',
    ];

    protected $table = 'paiements';

    protected $casts = [
        'paiement_date' => 'datetime',
        'status' => 'boolean',
        'revoked_at' => 'datetime',
    ];

    /**
     * Get the user that owns the Paiements
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the categorie that owns the Paiements
     */
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class, 'categorie_id', 'id');
    }

    public function codes(): HasMany
    {
        return $this->hasMany(Codes::class, 'paiements_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function granter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function isAdminGrant(): bool
    {
        return $this->origin === self::ORIGIN_ADMIN_GRANT;
    }

    /** Achats réels, hors codes offerts par l'administration. */
    public function scopePurchases(Builder $query): Builder
    {
        return $query->where('origin', self::ORIGIN_PURCHASE);
    }
}
