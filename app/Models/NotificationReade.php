<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationReade extends Model
{
    use HasFactory;
    
    protected $fillable = ['user_id', 'app_message_id'];
}
