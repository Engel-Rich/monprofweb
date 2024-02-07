<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Professeur extends Model
{
    
    use HasFactory;

    protected $fillable=['user_id', 'matieres_id'];

    public function user(): BelongsTo{
        return $this->belongsTo(User::class);
    }
    public function matiere(): BelongsTo{
        return $this->belongsTo(Matieres::class, 'matieres_id');
    }
}
