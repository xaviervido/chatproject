<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PresenceController extends Controller
{
    /**
     * Update user's online status in a group
     */
    public function updateStatus(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'is_online' => 'required|boolean',
        ]);

        if (!$group->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Not a member of this group',
            ], 403);
        }

        $group->members()->updateExistingPivot($user->id, [
            'is_online' => $validated['is_online'],
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'message' => 'Status updated successfully',
            'is_online' => $validated['is_online'],
        ]);
    }

    /**
     * Get all online users (all registered users except current user)
     * In a real distributed system, this would track active WebSocket connections
     */
    public function getOnlineUsers(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get all other users (for demo purposes, show all users as potentially online)
        // In production, you'd track actual WebSocket connections
        $onlineUsers = User::where('id', '!=', $user->id)
            ->select('id', 'name', 'email')
            ->get();

        return response()->json([
            'online_users' => $onlineUsers,
        ]);
    }

    /**
     * Heartbeat to keep user online
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $user = $request->user();

        // Update last_seen_at for all groups user is in
        $user->groups()->updateExistingPivot(
            $user->groups()->pluck('groups.id')->toArray(),
            ['last_seen_at' => now(), 'is_online' => true]
        );

        return response()->json([
            'message' => 'Heartbeat received',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Set user offline in all groups
     */
    public function goOffline(Request $request): JsonResponse
    {
        $user = $request->user();

        // Set user offline in all groups
        foreach ($user->groups as $group) {
            $group->members()->updateExistingPivot($user->id, [
                'is_online' => false,
                'last_seen_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'User set to offline',
        ]);
    }
}
