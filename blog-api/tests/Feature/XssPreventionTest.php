<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\User;

class XssPreventionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_xss_prevention_works()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', [
            'posts:read',
            'posts:write-own'
        ], now()->addDays(1))->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/posts', [
            'title' => 'Test Post',
            'content' => '<script>alert("XSS")</script>'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content'])
            ->assertJson(function ($json) {
                $json->has('errors.content')
                    ->etc();
            });
    }

    #[Test]
    public function test_xss_prevention_with_javascript_url()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', [
            'posts:read',
            'posts:write-own'
        ], now()->addDays(1))->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/posts', [
            'title' => 'Test Post',
            'content' => 'Click here: javascript:alert("XSS")'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    #[Test]
    public function test_valid_content_is_accepted()
    {
        $user = User::factory()->create([
            'email' => 'unique-xss-test@example.com'
        ]);
        $token = $user->createToken('test-token', [
            'posts:read',
            'posts:write-own'
        ], now()->addDays(1))->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/posts', [
            'title' => 'Test Post',
            'content' => 'This is safe content without any scripts.'
        ]);

        $response->assertStatus(201);
    }
}
