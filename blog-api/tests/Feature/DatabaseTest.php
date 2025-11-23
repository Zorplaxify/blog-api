<?php

use Illuminate\Foundation\Testing\TestCase;
use PHPUnit\Framework\Attributes\Test; 
use App\Models\Post;

class DatabaseTest extends TestCase
{
    #[Test]
    public function handle_large_number_of_posts()
    {
        Post::factory()->count(5000)->create();
        
        $response = $this->getJson('/api/posts?per_page=100');
        
        $response->assertStatus(200)
                ->assertJsonCount(100, 'data');
    }
}