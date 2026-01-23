<?php

namespace App\Http\Controllers;

use App\Http\Resources\LikeResource;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'user_id' => 'sometimes|integer|exists:users,id',
            'per_page' => 'sometimes|integer|min:5|max:100',
        ]);

        $targetUserId = $user->id;
        if (in_array($user->role, ['admin', 'moderator'], true) && $request->filled('user_id')) {
            $targetUserId = (int) $validated['user_id'];
        }

        $perPage = (int) ($validated['per_page'] ?? 50);

        $likes = Like::query()
            ->where('user_id', $targetUserId)
            ->with([
                'user',
                'post' => function ($q) {
                    $q->with(['author', 'theme'])
                        ->withCount(['likes', 'replies']);
                },
            ])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'user_id' => $targetUserId,
            'likes' => LikeResource::collection($likes),
            'meta' => [
                'current_page' => $likes->currentPage(),
                'per_page' => $likes->perPage(),
                'total' => $likes->total(),
                'last_page' => $likes->lastPage(),
            ],
        ]);
    }

    public function like(Post $post)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        Like::firstOrCreate([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $post->loadCount(['likes', 'replies']);

        return response()->json([
            'message' => 'Liked',
            'liked' => true,
            'post_id' => $post->id,
            'likes_count'  => $post->likes_count,
            'replies_count' => $post->replies_count,
        ]);
    }

    public function unlike(Post $post)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        Like::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->delete();

        $post->loadCount(['likes', 'replies']);

        return response()->json([
            'message' => 'Unliked',
            'liked' => false,
            'post_id' => $post->id,
            'likes_count' => $post->likes_count,
            'replies_count' => $post->replies_count,
        ]);
    }
}
