<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Eleve extends Model
{
    use HasFactory;
    protected $fillable = ['sexe','etablissement','user_id', "classe_id"];

    public function classe():BelongsTo{
        return $this->belongsTo(Classe::class);
    }
}
