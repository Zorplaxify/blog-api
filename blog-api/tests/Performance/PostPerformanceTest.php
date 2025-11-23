<?php

use Illuminate\Foundation\Testing\TestCase;
use PHPUnit\Framework\Attributes\Test; 
use App\Models\Post;


class PostPerformanceTest extends TestCase
{
    #[Test]
    public function posts_index_performance()
    {
        Post::factory()->count(1000)->create();
        
        $start = microtime(true);
        
        $response = $this->getJson('/api/posts');
        
        $end = microtime(true);
        $responseTime = ($end - $start) * 1000; 
        
        $response->assertStatus(200);
        $this->assertLessThan(500, $responseTime); 
    }
}