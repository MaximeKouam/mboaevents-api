<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventProgramItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'day_number', 'day_label', 'date_label',
        'starts_at', 'title', 'speaker', 'tag', 'position',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'day_number' => 'integer',
        'position' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
