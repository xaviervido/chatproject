<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'created_by',
    ];

    /**
     * Get the creator of the group
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get members of the group
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['is_online', 'last_seen_at'])
            ->withTimestamps();
    }

    /**
     * Get online members of the group
     */
    public function onlineMembers(): BelongsToMany
    {
        return $this->members()->wherePivot('is_online', true);
    }

    /**
     * Get messages in the group
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->where('type', 'group');
    }
}
