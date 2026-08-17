<?php

namespace App\Models;

use App\Services\FileManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PaymentProvider extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'image', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'image_url',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(PayementServices::class, 'payment_provider_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (blank($this->image)) {
            return null;
        }

        if (Str::startsWith($this->image, ['http://', 'https://'])) {
            return $this->image;
        }

        if (Str::startsWith($this->image, ['/', 'images/', 'storage/'])) {
            return asset(ltrim($this->image, '/'));
        }

        return app(FileManager::class, ['filefolder' => 'payment/providers'])->get($this->image);
    }
}
