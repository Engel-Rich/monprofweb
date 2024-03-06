<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Questions extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'description',
        'classe_id',
        'categorie_id',
        'eleve_id',
        'matieres_id', 'image_url','questions_id'
    ];

    /**
     * Get the reponse that owns the Questions
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }
    /**
     * Get the reponse that owns the Questions
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matieres::class, 'matieres_id');
    }
    /**
     * Get the reponse that owns the Questions
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }
    /**
     * Get the reponse that owns the Questions
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reponse(): HasOne
    {
        return $this->hasOne(Reponses::class, 'questions_id');
    }
    /**
     * Get the reponse that owns the Questions
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }
}
