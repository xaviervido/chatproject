<?php

use App\Models\Group;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| WebSocket channels for distributed communication
| - Presence channels for group chat (multicast)
| - Private channels for direct messages (unicast)
|
*/

/**
 * Presence channel for group chat
 * Allows tracking of who is online in each group
 */
Broadcast::channel('group.{groupId}', function ($user, $groupId) {
    $group = Group::find($groupId);

    if (!$group) {
        return false;
    }

    // Check if user is a member of the group
    $isMember = $group->members()->where('user_id', $user->id)->exists();

    if ($isMember) {
        // Return user data for presence tracking
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    return false;
});

/**
 * Private channel for direct messages (unicast)
 * Only the specific user can listen to their own channel
 */
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

/**
 * Default user channel
 */
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
