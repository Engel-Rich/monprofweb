<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Classe extends Model
{
    use HasFactory;

    protected  $fillable = ['libelle','short_name', 'description'];

    /**
     * The matiers that belong to the Classe
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function matieres(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Matieres::class, 'classe_matiere', 'classe_id', 'matieres_id');
    }
}
