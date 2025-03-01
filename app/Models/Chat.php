<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'image'
    ];

    /**
     * Get the users in this chat.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_user')
            ->withPivot('is_admin', 'last_read_at')
            ->withTimestamps();
    }

    /**
     * Get the messages in this chat.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the admins of this chat (for group chats).
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_user')
            ->wherePivot('is_admin', true);
    }

    /**
     * Check if the chat is a group chat.
     */
    public function isGroupChat(): bool
    {
        return $this->type === 'group';
    }
}