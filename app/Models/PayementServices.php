<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayementServices extends Model
{
    use HasFactory;
    protected $table = 'payment_services';
    protected $fillable = [
        'title',
        'img',
        'description',
        'status',
        'subtitle',
        'is_active',
        'reg_exp',
        'subscription_id',
        'sens',
    ];
}
