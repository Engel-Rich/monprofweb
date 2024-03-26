<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Matieres extends Model
{
    use HasFactory;

    protected $fillable = ["libelle", "description","app_name"];

    /**
     * The roles that belong to the Matieres
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function classe(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Classe::class, 'classe_matiere', 'matieres_id', 'classe_id');
    }
}
