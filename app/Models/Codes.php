<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Codes extends Model
{
    use HasFactory;
    protected $fillable = ['code', 'paiements_id', 'eleve_id','active_date','actif'];
}
