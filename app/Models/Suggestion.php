<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Suggestion extends Model
{
    protected $table ="sugestions";
    protected $fillable = ['title', 'body','user_id'];
    use HasFactory;
}
