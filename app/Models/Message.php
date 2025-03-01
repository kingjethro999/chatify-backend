<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'user_id',
        'content',
        'type',
        'file_url',
        'is_delivered',
        'is_seen'
    ];

    protected $casts = [
        'is_delivered' => 'boolean',
        'is_seen' => 'boolean'
    ];

    /**
     * Get the chat that owns the message.
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Get the user who sent the message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the message contains media.
     */
    public function hasMedia(): bool
    {
        return in_array($this->type, ['image', 'video', 'audio', 'document']);
    }
}