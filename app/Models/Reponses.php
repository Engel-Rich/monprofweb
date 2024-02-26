<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reponses extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'questions_id',
        'description',
        'image_url','user_id'
    ];
}
