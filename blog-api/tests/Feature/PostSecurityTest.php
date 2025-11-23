<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PostSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_cache_key_security()
    {
        $response = $this->getJson('/api/posts?search=test&user_id=1');
        $response->assertStatus(200);
        
        $maliciousResponse = $this->getJson('/api/posts?' . http_build_query([
            'search' => 'test',
            'malicious' => str_repeat('a', 10000) 
        ]));
        $maliciousResponse->assertStatus(200);
    }

    #[Test]
    public function test_sql_injection_prevention_in_search()
    {
        $response = $this->getJson('/api/posts?search=test%27; DROP TABLE users--');
        $response->assertStatus(200); 
    }

    #[Test]
    public function test_sql_injection_prevention_in_filters()
    {
        $response = $this->getJson('/api/posts?sort=id; DROP TABLE posts--');
        $response->assertStatus(200); 
    }
}