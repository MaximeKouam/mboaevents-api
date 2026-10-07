<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizerPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'slug', 'bio', 'logo_path', 'cover_path', 'social_links', 'verified_at',
    ];

    protected $casts = [
        'social_links' => 'array',
        'verified_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function followers(): HasMany
    {
        return $this->hasMany(Follow::class);
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}
