<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Follow extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'organizer_page_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organizerPage(): BelongsTo
    {
        return $this->belongsTo(OrganizerPage::class);
    }
}
