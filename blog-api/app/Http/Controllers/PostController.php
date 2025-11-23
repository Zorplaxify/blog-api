<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Http\Requests\StorePostRequest;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\PostCollection;
use App\Http\Resources\PostResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $cacheKey = 'posts.' . md5(serialize($request->all()));
        
        $posts = Cache::remember($cacheKey, 60, function() use ($request) {
            $query = Post::with('user');
            
            // Search/filter
            if ($request->has('search')) {
                $query->where(function($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('content', 'like', '%' . $request->search . '%');
                });
            }
            
            // Filter by user
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }
            
            // Sort
            $sort = $request->get('sort', 'created_at');
            $direction = $request->get('direction', 'desc');
            
            // Validate sort direction
            $direction = in_array(strtolower($direction), ['asc', 'desc']) ? $direction : 'desc';
            
            return $query->orderBy($sort, $direction)
                        ->paginate($request->get('per_page', 10));
        });
    
        return new PostCollection($posts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        $post = Post::create([
            'title' => $request->title,
            'content' => $request->content,
            'user_id' => auth('sanctum')->id(),
        ]);
        
        return new PostResource($post->load('user'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $post = Post::with('user')->findOrFail($id);
            return new PostResource($post);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Post not found',
                'message' => 'The requested post does not exist'
            ], 404);
        }
    }
    
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $post = Post::findOrFail($id);
            
            if ($post->user_id !== auth('sanctum')->id()) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'You can only update your own posts'
                ], 403);
            }
            
            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'content' => 'sometimes|required|string'
            ]);
            
            $post->update($validated);
            
            return new PostResource($post->load('user'));
            
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Post not found',
                'message' => 'The requested post does not exist'
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $post = Post::findOrFail($id);
            
            if ($post->user_id !== auth('sanctum')->id()) {
                return response()->json([
                    'error' => 'Unauthorized', 
                    'message' => 'You can only delete your own posts'
                ], 403);
            }
            
            $post->delete();
            
            return response()->json([
                'message' => 'Post deleted successfully'
            ], 200);
            
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Post not found',
                'message' => 'The requested post does not exist'
            ], 404);
        }
    }
}
