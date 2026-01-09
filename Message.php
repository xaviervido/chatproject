<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'group_id',
        'receiver_id',
        'content',
        'type',
        'is_delivered',
        'is_read',
    ];

    protected $casts = [
        'is_delivered' => 'boolean',
        'is_read' => 'boolean',
    ];

    /**
     * Get the sender of the message
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the group (for group messages)
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the receiver (for private messages)
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Scope for group messages
     */
    public function scopeGroupMessages($query)
    {
        return $query->where('type', 'group');
    }

    /**
     * Scope for private messages
     */
    public function scopePrivateMessages($query)
    {
        return $query->where('type', 'private');
    }
}
