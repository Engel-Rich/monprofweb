<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'transaction_id',
        'local_reference',
        'provider_reference',
        'provider_code',
        'event',
        'source',
        'status_from',
        'status_to',
        'actor_id',
        'ip_address',
        'changes',
        'payload',
    ];

    protected $casts = [
        'changes' => 'array',
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
