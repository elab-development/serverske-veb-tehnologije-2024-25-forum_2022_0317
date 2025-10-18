<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function toggleCanPublish(User $user)
    {
        $actor = Auth::user();
        if (!$actor) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if (!in_array($actor->role, ['admin', 'moderator'], true)) {
            return response()->json(['error' => 'Only admins and moderators can change publishing rights'], 403);
        }

        if ($actor->role === 'moderator' && $user->role !== 'user') {
            return response()->json(['error' => 'Moderators can only update regular users'], 403);
        }

        $user->can_publish = !$user->can_publish;
        $user->save();

        return response()->json([
            'message' => 'User can_publish toggled successfully',
            'user' => new UserResource($user),
        ]);
    }
}
