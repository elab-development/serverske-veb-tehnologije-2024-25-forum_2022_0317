<?php

namespace App\Http\Controllers;

use App\Http\Resources\LikeResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(5, min(100, $perPage));

        $sortBy  = $request->query('sort_by', 'created_at');
        $sortDir = strtolower($request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['created_at', 'replies_count', 'likes_count', 'title'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $q = Post::query()
            ->whereNull('replied_to_id')
            ->with(['author', 'theme'])
            ->withCount(['replies', 'likes']);

        if (Auth::check()) {
            $q->with(['likes' => function ($likeQ) {
                $likeQ->where('user_id', Auth::id());
            }]);
        }

        // Filter: tema po name ili ID
        if ($request->filled('theme')) {
            $themeName = $request->query('theme');
            $q->whereHas('theme', fn($tq) => $tq->where('name', $themeName));
        } elseif ($request->filled('theme_id')) {
            $q->where('theme_id', (int) $request->query('theme_id'));
        }

        // Filter: autor po ID ili imenu
        if ($request->filled('author_id')) {
            $q->where('user_id', (int) $request->query('author_id'));
        } elseif ($request->filled('author')) {
            $name = $request->query('author');
            $q->whereHas('author', fn($uq) => $uq->where('name', 'like', "%{$name}%"));
        }

        // Pretraga po title/content
        if ($request->filled('q')) {
            $term = $request->query('q');
            $q->where(function ($s) use ($term) {
                $s->where('title', 'like', "%{$term}%")
                    ->orWhere('content', 'like', "%{$term}%");
            });
        }

        if ($request->filled('from')) {
            $q->whereDate('created_at', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('created_at', '<=', $request->query('to'));
        }

        $q->orderBy($sortBy, $sortDir);

        $posts = $q->paginate($perPage);

        return response()->json([
            'posts' => PostResource::collection($posts),
            'meta'  => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'user' || !$user->can_publish) {
            return response()->json([
                'error' => 'Only users with publishing rights can create posts',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'theme' => 'sometimes|string|exists:themes,name',
            'theme_id' => 'sometimes|integer|exists:themes,id',
            'replied_to_id' => 'sometimes|nullable|integer|exists:posts,id',
        ]);

        $themeId = null;
        if (isset($validated['theme_id'])) {
            $themeId = (int) $validated['theme_id'];
        } elseif (isset($validated['theme'])) {
            $themeId = Theme::where('name', $validated['theme'])->value('id');
        }

        if (!empty($validated['replied_to_id'])) {
            $parent = Post::find($validated['replied_to_id']);
            if (!$parent) {
                return response()->json(['error' => 'Parent post not found'], 422);
            }
            $themeId = $themeId ?: $parent->theme_id;

            if ($themeId !== $parent->theme_id) {
                return response()->json([
                    'error' => 'Reply must be in the same theme as its parent post',
                ], 422);
            }
        }

        if (!$themeId) {
            return response()->json([
                'error' => 'Theme is required (theme name or theme_id)',
            ], 422);
        }

        $post = Post::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'user_id' => $user->id,
            'theme_id' => $themeId,
            'replied_to_id' => $validated['replied_to_id'] ?? null,
        ]);

        $post->load(['author', 'theme'])->loadCount(['replies', 'likes']);

        if (Auth::check()) {
            $post->load(['likes' => fn($q) => $q->where('user_id', Auth::id())]);
        }

        return response()->json([
            'message' => 'Post created successfully',
            'post'    => new PostResource($post),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        $post->load([
            'author',
            'theme',
            'parent',
        ])->loadCount(['replies', 'likes']);

        if (Auth::check()) {
            $post->load(['likes' => function ($q) {
                $q->where('user_id', Auth::id());
            }]);
        }

        return response()->json([
            'post' => new PostResource($post),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        $user = Auth::user();
        if (!$user || $user->id !== $post->user_id) {
            return response()->json(['error' => 'You can only delete your own posts'], 403);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }

    public function replies(Post $post, Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(5, min(100, $perPage));

        $replies = $post->replies()
            ->with(['author', 'theme'])
            ->withCount(['replies', 'likes'])
            ->latest()
            ->paginate($perPage);

        if (Auth::check()) {
            $replies->getCollection()->load(['likes' => fn($q) => $q->where('user_id', Auth::id())]);
        }

        return response()->json([
            'post_id' => $post->id,
            'replies' => PostResource::collection($replies),
            'meta'    => [
                'current_page' => $replies->currentPage(),
                'per_page' => $replies->perPage(),
                'total' => $replies->total(),
                'last_page' => $replies->lastPage(),
            ],
        ]);
    }

    public function likes(Post $post, Request $request)
    {
        $perPage = (int) $request->query('per_page', 50);
        $perPage = max(5, min(100, $perPage));

        $likes = $post->likes()
            ->with('user')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'post_id' => $post->id,
            'likes' => LikeResource::collection($likes),
            'meta' => [
                'current_page' => $likes->currentPage(),
                'per_page' => $likes->perPage(),
                'total' => $likes->total(),
                'last_page' => $likes->lastPage(),
            ],
        ]);
    }
}
