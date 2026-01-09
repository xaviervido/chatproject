<?php

namespace App\Services;

use App\Events\UserJoinedEvent;
use App\Events\UserLeftEvent;
use App\Models\Group;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing active membership in groups
 * Implements distributed systems concepts for tracking member presence
 */
class MembershipService
{
    /**
     * Timeout in minutes after which a user is considered offline
     */
    private const OFFLINE_TIMEOUT_MINUTES = 5;

    /**
     * Add a user to a group
     */
    public function joinGroup(User $user, Group $group): bool
    {
        if ($group->members()->where('user_id', $user->id)->exists()) {
            // Update online status
            $this->setOnline($user, $group);
            return true;
        }

        $group->members()->attach($user->id, [
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        // Broadcast join event
        broadcast(new UserJoinedEvent($user, $group))->toOthers();

        Log::channel('chat')->info('Membership: User joined group', [
            'user_id' => $user->id,
            'group_id' => $group->id,
        ]);

        return true;
    }

    /**
     * Remove a user from a group
     */
    public function leaveGroup(User $user, Group $group): bool
    {
        if (!$group->members()->where('user_id', $user->id)->exists()) {
            return false;
        }

        // Broadcast leave event before removing
        broadcast(new UserLeftEvent($user, $group))->toOthers();

        $group->members()->detach($user->id);

        Log::channel('chat')->info('Membership: User left group', [
            'user_id' => $user->id,
            'group_id' => $group->id,
        ]);

        return true;
    }

    /**
     * Set user as online in a group
     */
    public function setOnline(User $user, Group $group): void
    {
        $group->members()->updateExistingPivot($user->id, [
            'is_online' => true,
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Set user as offline in a group
     */
    public function setOffline(User $user, Group $group): void
    {
        $group->members()->updateExistingPivot($user->id, [
            'is_online' => false,
            'last_seen_at' => now(),
        ]);

        // Broadcast leave event
        broadcast(new UserLeftEvent($user, $group))->toOthers();
    }

    /**
     * Set user as offline in all groups
     */
    public function setOfflineGlobally(User $user): void
    {
        foreach ($user->groups as $group) {
            $this->setOffline($user, $group);
        }
    }

    /**
     * Get all online members in a group
     */
    public function getOnlineMembers(Group $group): Collection
    {
        return $group->members()
            ->wherePivot('is_online', true)
            ->get(['users.id', 'users.name', 'users.email']);
    }

    /**
     * Get all active members in a group (based on last_seen_at)
     */
    public function getActiveMembers(Group $group): Collection
    {
        $timeout = now()->subMinutes(self::OFFLINE_TIMEOUT_MINUTES);

        return $group->members()
            ->wherePivot('last_seen_at', '>=', $timeout)
            ->get(['users.id', 'users.name', 'users.email']);
    }

    /**
     * Update heartbeat for a user
     */
    public function heartbeat(User $user): void
    {
        foreach ($user->groups as $group) {
            $group->members()->updateExistingPivot($user->id, [
                'last_seen_at' => now(),
                'is_online' => true,
            ]);
        }
    }

    /**
     * Check and update stale online statuses
     * Call this periodically to clean up users who disconnected without proper logout
     */
    public function cleanupStaleConnections(): int
    {
        $timeout = now()->subMinutes(self::OFFLINE_TIMEOUT_MINUTES);
        $count = 0;

        $staleRecords = \DB::table('group_user')
            ->where('is_online', true)
            ->where('last_seen_at', '<', $timeout)
            ->get();

        foreach ($staleRecords as $record) {
            \DB::table('group_user')
                ->where('group_id', $record->group_id)
                ->where('user_id', $record->user_id)
                ->update(['is_online' => false]);

            $count++;

            Log::channel('chat')->info('Membership: Cleaned up stale connection', [
                'user_id' => $record->user_id,
                'group_id' => $record->group_id,
            ]);
        }

        return $count;
    }

    /**
     * Check if a user is a member of a group
     */
    public function isMember(User $user, Group $group): bool
    {
        return $group->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if a user is online in a group
     */
    public function isOnline(User $user, Group $group): bool
    {
        $member = $group->members()->where('user_id', $user->id)->first();
        return $member && $member->pivot->is_online;
    }
}
