<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'payment_provider_id',
        'user_id',
        'amount',
        'base_amount',
        'service_fee',
        'provider_fee_percentage',
        'user_fee_percentage',
        'phone_number',
        'status',
        'sens',
        'internal_service',
        'subscription_id',
        'service_id',
        'provider_reference',
        'payment_token',
        'reference',
        'raison_reject',
        'metadatas',
        'conclusion_method',
    ];

    protected $casts = [
        'metadatas' => 'array',
        'amount' => 'float',
        'base_amount' => 'float',
        'service_fee' => 'float',
        'provider_fee_percentage' => 'float',
        'user_fee_percentage' => 'float',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(PaymentProvider::class, 'payment_provider_id');
    }

    public function paymentService(): BelongsTo
    {
        return $this->belongsTo(PayementServices::class, 'service_id');
    }

    public function paiement(): HasOne
    {
        return $this->hasOne(Paiements::class, 'transaction_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TransactionLog::class)->latest('id');
    }
}
