<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSpeaker extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'name', 'role', 'image_path', 'tag',
        'twitter', 'linkedin', 'website', 'position',
    ];

    protected $casts = ['position' => 'integer'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
