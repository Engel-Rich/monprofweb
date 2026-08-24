<?php

namespace App\Models;

use App\Services\FileManager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PayementServices extends Model
{
    use HasFactory;

    protected $table = 'payment_services';

    protected $fillable = [
        'payment_provider_id',
        'title',
        'img',
        'description',
        'status',
        'subtitle',
        'is_active',
        'reg_exp',
        'subscription_id',
        'sens',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'status' => 'integer',
    ];

    protected $appends = [
        'image_url',
        'provider_service_id',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(PaymentProvider::class, 'payment_provider_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (blank($this->img)) {
            return null;
        }

        if (Str::startsWith($this->img, ['http://', 'https://'])) {
            return $this->img;
        }

        if (Str::startsWith($this->img, ['/', 'images/', 'storage/'])) {
            return asset(ltrim($this->img, '/'));
        }

        return app(FileManager::class, ['filefolder' => 'payment/services'])->get($this->img);
    }

    /**
     * Nom explicite exposé aux nouveaux clients. subscription_id reste présent
     * pour les versions mobiles déjà déployées.
     */
    public function getProviderServiceIdAttribute(): ?int
    {
        return filled($this->subscription_id) ? (int) $this->subscription_id : null;
    }
}
