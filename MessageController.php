<?php

namespace App\Http\Controllers;

use App\Events\GroupMessageEvent;
use App\Events\PrivateMessageEvent;
use App\Models\Group;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    /**
     * Send a group message (multicast)
     */
    public function sendGroupMessage(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();

        // Verify user is a member of the group
        if (!$group->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'You are not a member of this group',
            ], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $message = Message::create([
            'sender_id' => $user->id,
            'group_id' => $group->id,
            'content' => $validated['content'],
            'type' => 'group',
        ]);

        $message->load('sender:id,name');

        // Broadcast to all group members
        broadcast(new GroupMessageEvent($message))->toOthers();

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => [
                'id' => $message->id,
                'content' => $message->content,
                'type' => $message->type,
                'sender' => $message->sender,
                'created_at' => $message->created_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Send a private message (unicast)
     */
    public function sendPrivateMessage(Request $request, User $receiver): JsonResponse
    {
        $sender = $request->user();

        if ($sender->id === $receiver->id) {
            return response()->json([
                'message' => 'Cannot send message to yourself',
            ], 400);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $message = Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'content' => $validated['content'],
            'type' => 'private',
        ]);

        $message->load(['sender:id,name', 'receiver:id,name']);

        // Broadcast to the specific receiver (unicast)
        broadcast(new PrivateMessageEvent($message))->toOthers();

        return response()->json([
            'message' => 'Private message sent successfully',
            'data' => [
                'id' => $message->id,
                'content' => $message->content,
                'type' => $message->type,
                'sender' => $message->sender,
                'receiver' => $message->receiver,
                'created_at' => $message->created_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Get group message history
     */
    public function getGroupMessages(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();

        // Verify user is a member of the group
        if (!$group->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'You are not a member of this group',
            ], 403);
        }

        $messages = Message::where('group_id', $group->id)
            ->where('type', 'group')
            ->with('sender:id,name')
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        return response()->json([
            'messages' => $messages,
        ]);
    }

    /**
     * Get private message history between two users
     */
    public function getPrivateMessages(Request $request, User $otherUser): JsonResponse
    {
        $user = $request->user();

        $messages = Message::where('type', 'private')
            ->where(function ($query) use ($user, $otherUser) {
                $query->where(function ($q) use ($user, $otherUser) {
                    $q->where('sender_id', $user->id)
                        ->where('receiver_id', $otherUser->id);
                })->orWhere(function ($q) use ($user, $otherUser) {
                    $q->where('sender_id', $otherUser->id)
                        ->where('receiver_id', $user->id);
                });
            })
            ->with(['sender:id,name', 'receiver:id,name'])
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        // Mark messages as read
        Message::where('type', 'private')
            ->where('sender_id', $otherUser->id)
            ->where('receiver_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'messages' => $messages,
        ]);
    }

    /**
     * Mark message as delivered (acknowledgment)
     */
    public function markDelivered(Message $message): JsonResponse
    {
        $message->update(['is_delivered' => true]);

        return response()->json([
            'message' => 'Message marked as delivered',
        ]);
    }

    /**
     * Mark message as read (acknowledgment)
     */
    public function markRead(Message $message): JsonResponse
    {
        $message->update(['is_read' => true]);

        return response()->json([
            'message' => 'Message marked as read',
        ]);
    }
}
