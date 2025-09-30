<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';
    protected $fillable = [
        'user_id',
        'amount',
        'phone_number',
        'status',
        'sens',
        'internal_service',
        'subscription_id',
        'service_id',
        'transaction_id',
        'payment_token',
        'reference',
        "raison_reject"
    ];
}
