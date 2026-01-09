<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PresenceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Distributed Chat System API Routes
| All routes use message passing for distributed communication
|
*/

// Public routes (no authentication required)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);

    // Groups
    Route::prefix('groups')->group(function () {
        Route::get('/', [GroupController::class, 'index']);
        Route::post('/', [GroupController::class, 'store']);
        Route::get('/my-groups', [GroupController::class, 'myGroups']);
        Route::get('/{group}', [GroupController::class, 'show']);
        Route::post('/{group}/join', [GroupController::class, 'join']);
        Route::post('/{group}/leave', [GroupController::class, 'leave']);
        Route::get('/{group}/online-members', [GroupController::class, 'onlineMembers']);
    });

    // Messages
    Route::prefix('messages')->group(function () {
        // Group messages (multicast)
        Route::post('/group/{group}', [MessageController::class, 'sendGroupMessage']);
        Route::get('/group/{group}', [MessageController::class, 'getGroupMessages']);

        // Private messages (unicast)
        Route::post('/private/{receiver}', [MessageController::class, 'sendPrivateMessage']);
        Route::get('/private/{otherUser}', [MessageController::class, 'getPrivateMessages']);

        // Message acknowledgments
        Route::patch('/{message}/delivered', [MessageController::class, 'markDelivered']);
        Route::patch('/{message}/read', [MessageController::class, 'markRead']);
    });

    // Presence/Membership
    Route::prefix('presence')->group(function () {
        Route::post('/heartbeat', [PresenceController::class, 'heartbeat']);
        Route::post('/offline', [PresenceController::class, 'goOffline']);
        Route::get('/online-users', [PresenceController::class, 'getOnlineUsers']);
        Route::post('/group/{group}/status', [PresenceController::class, 'updateStatus']);
    });
});
