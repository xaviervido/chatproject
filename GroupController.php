<?php

namespace App\Http\Controllers;

use App\Events\UserJoinedEvent;
use App\Events\UserLeftEvent;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GroupController extends Controller
{
    /**
     * Get all groups
     */
    public function index(Request $request): JsonResponse
    {
        $groups = Group::with(['creator:id,name', 'members:id,name'])
            ->withCount('members')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'groups' => $groups,
        ]);
    }

    /**
     * Create a new group
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $group = Group::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        // Auto-join creator to the group
        $group->members()->attach($request->user()->id, ['is_online' => true]);

        $group->load(['creator:id,name', 'members:id,name']);

        return response()->json([
            'message' => 'Group created successfully',
            'group' => $group,
        ], 201);
    }

    /**
     * Get a specific group
     */
    public function show(Group $group): JsonResponse
    {
        $group->load(['creator:id,name', 'members:id,name,email']);
        $group->loadCount('members');

        return response()->json([
            'group' => $group,
        ]);
    }

    /**
     * Join a group
     */
    public function join(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();

        // Check if already a member
        if ($group->members()->where('user_id', $user->id)->exists()) {
            // Update online status
            $group->members()->updateExistingPivot($user->id, [
                'is_online' => true,
                'last_seen_at' => now(),
            ]);

            return response()->json([
                'message' => 'Already a member, status updated to online',
                'group' => $group->load('members:id,name'),
            ]);
        }

        // Add user to group
        $group->members()->attach($user->id, [
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        // Broadcast user joined event
        broadcast(new UserJoinedEvent($user, $group))->toOthers();

        $group->load('members:id,name');

        return response()->json([
            'message' => 'Joined group successfully',
            'group' => $group,
        ]);
    }

    /**
     * Leave a group
     */
    public function leave(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();

        // Check if member
        if (!$group->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Not a member of this group',
            ], 400);
        }

        // Broadcast user left event before removing
        broadcast(new UserLeftEvent($user, $group))->toOthers();

        // Remove user from group
        $group->members()->detach($user->id);

        return response()->json([
            'message' => 'Left group successfully',
        ]);
    }

    /**
     * Get online members in a group
     */
    public function onlineMembers(Group $group): JsonResponse
    {
        $onlineMembers = $group->members()
            ->wherePivot('is_online', true)
            ->get(['users.id', 'users.name', 'users.email']);

        return response()->json([
            'online_members' => $onlineMembers,
        ]);
    }

    /**
     * Get my groups
     */
    public function myGroups(Request $request): JsonResponse
    {
        $groups = $request->user()
            ->groups()
            ->with(['creator:id,name'])
            ->withCount('members')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'groups' => $groups,
        ]);
    }
}
