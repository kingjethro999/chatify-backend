<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusPrivacy extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'privacy_type',
        'selected_users'
    ];

    protected $casts = [
        'selected_users' => 'array'
    ];

    /**
     * Get the user who owns these privacy settings.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if a user can view statuses based on privacy settings.
     */
    public function canUserViewStatus(User $viewer): bool
    {
        if ($this->privacy_type === 'all') {
            return true;
        }

        $isSelected = in_array($viewer->id, $this->selected_users ?? []);
        return $this->privacy_type === 'selected' ? $isSelected : !$isSelected;
    }
}