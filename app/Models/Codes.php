<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Codes extends Model
{
    use HasFactory;
    protected $fillable = ['code', 'paiements_id', 'eleve_id','active_date','actif'];

    /**
     * Get the eleve that owns the Codes
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class, 'eleve_id');
    }

     /**
     * Get the eleve that owns the Codes
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paiement(): BelongsTo
    {
        return $this->belongsTo(Paiements::class, 'paiements_id');
    }
}
