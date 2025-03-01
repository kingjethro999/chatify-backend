<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusViewer extends Model
{
    use HasFactory;

    protected $fillable = [
        'status_id',
        'user_id',
        'viewed_at'
    ];

    protected $casts = [
        'viewed_at' => 'datetime'
    ];

    /**
     * Get the status that was viewed.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * Get the user who viewed the status.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}