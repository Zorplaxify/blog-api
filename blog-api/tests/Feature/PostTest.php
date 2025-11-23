<?php

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test; 
use App\Models\User;
use App\Models\Post;

class PostTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_create_post()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/posts', [
            'title' => 'Test Post',
            'content' => 'Test content'
        ]);

        $response->assertStatus(201)
                ->assertJsonFragment(['title' => 'Test Post']);
    }

    #[Test]
    public function user_cannot_update_others_post()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $post = Post::factory()->create(['user_id' => $user1->id]);
        
        $token = $user2->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson("/api/posts/{$post->id}", [
            'title' => 'Updated Title'
        ]);

        $response->assertStatus(403);
    }
}