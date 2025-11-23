<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function registration_rate_limited()
    {
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/auth/register', [
                'name' => "User {$i}",
                'email' => "user{$i}@example.com", 
                'password' => 'password123',
                'password_confirmation' => 'password123'
            ]);
            
            $this->assertNotEquals(429, $response->status(), "Request {$i} should not be rate limited");
        }

        $response = $this->postJson('/api/auth/register', [
            'name' => "User 11",
            'email' => "user11@example.com",
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response->assertStatus(429);
    }
}