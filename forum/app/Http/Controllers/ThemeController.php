<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Http\Resources\ThemeResource;
use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ThemeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $themes = Theme::withCount('posts')->orderBy('name')->get();

        if ($themes->isEmpty()) {
            return response()->json('No themes found.', 404);
        }

        return response()->json([
            'themes' => ThemeResource::collection($themes),
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
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Only admins can create themes'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:themes,name',
            'description' => 'nullable|string',
        ]);

        $theme = Theme::create($validated);

        return response()->json([
            'message' => 'Theme created successfully',
            'theme' => new ThemeResource($theme),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Theme $theme)
    {
        $theme->loadCount('posts');

        return response()->json([
            'theme' => new ThemeResource($theme),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Theme $theme)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Theme $theme)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Only admins can update themes'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:themes,name,' . $theme->id,
            'description' => 'sometimes|nullable|string',
        ]);

        $theme->update($validated);

        return response()->json([
            'message' => 'Theme updated successfully',
            'theme' => new ThemeResource($theme),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Theme $theme)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Only admins can delete themes'], 403);
        }

        $theme->delete();

        return response()->json(['message' => 'Theme deleted successfully']);
    }

    public function posts(Theme $theme)
    {
        $posts = $theme->posts()
            ->with(['author', 'theme'])
            ->withCount(['replies', 'likes'])
            ->latest()
            ->get();

        return response()->json([
            'theme' => new ThemeResource($theme->loadCount('posts')),
            'posts' => PostResource::collection($posts),
        ]);
    }
}
