<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Status extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'content',
        'media_url',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime'
    ];

    /**
     * Get the user who created the status.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the users who viewed this status.
     */
    public function viewers(): HasMany
    {
        return $this->hasMany(StatusViewer::class);
    }

    /**
     * Get the privacy settings for this status.
     */
    public function privacy(): HasOne
    {
        return $this->hasOne(StatusPrivacy::class, 'user_id', 'user_id');
    }

    /**
     * Check if the status has media.
     */
    public function hasMedia(): bool
    {
        return in_array($this->type, ['image', 'video']);
    }

    /**
     * Check if the status has expired.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}