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
        $cacheKey = $this->generateSafeCacheKey($request);
        
        $posts = Cache::remember($cacheKey, 60, function() use ($request) {
            $query = Post::with('user');
            
            if ($request->filled('search')) {
                $searchTerm = $request->input('search');
            
                $query->where('title', 'LIKE', "%{$searchTerm}%")
                ->orWhere('content', 'LIKE', "%{$searchTerm}%");
            }
            
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }
            
            $allowedSorts = ['created_at', 'title', 'updated_at'];
            $sort = in_array($request->get('sort'), $allowedSorts) 
                ? $request->get('sort') 
                : 'created_at';
                
            $direction = in_array(strtolower($request->get('direction')), ['asc', 'desc']) 
                ? $request->get('direction') 
                : 'desc';
            
            $perPage = min($request->get('per_page', 10), 100); 
            
            return $query->orderBy($sort, $direction)
                        ->paginate($perPage);
        });
    
        return new PostCollection($posts);
    }

    /**
     * Generate safe cache key
     */
    private function generateSafeCacheKey(Request $request): string
    {
        $allowedParams = ['search', 'user_id', 'sort', 'direction', 'per_page', 'page'];
        
        $normalizedParams = [];
        
        foreach ($allowedParams as $param) {
            if ($request->has($param)) {
                $value = $request->input($param);
                
                if (is_string($value)) {
                    $value = substr($value, 0, 50); 
                }
                
                $normalizedParams[$param] = $value;
            }
        }
        
        if (empty($normalizedParams)) {
            return 'posts.default';
        }
        
        $serialized = serialize($normalizedParams);
        
        if (strlen($serialized) > 1000) {
            return 'posts.large_query_' . md5($serialized);
        }
        
        return 'posts.' . md5($serialized);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        $post = $request->user()->posts()->create($request->validated());
    
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