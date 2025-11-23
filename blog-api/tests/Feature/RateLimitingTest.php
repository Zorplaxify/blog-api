<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'https://api.pwnedpasswords.com/*' => Http::response('', 200) 
        ]);

        RateLimiter::clear('throttle:' . request()->ip());
    }

    #[Test]
    public function registration_is_rate_limited_after_limit_is_hit()
    {
        $this->travelTo(now()->startOfMinute());

        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/auth/register', [
                'name' => 'User '.$i,
                'email' => "user{$i}" . uniqid() . "@example.com", 
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

            $response->assertStatus(201, "Unexpected fail at request {$i}");
        }

        $response = $this->postJson('/api/auth/register', [
            'name' => 'User X',
            'email' => "userX@example.com",
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(429);
    }

    #[Test]
    public function login_is_rate_limited_after_failed_attempts()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!')
        ]);

        $this->travelTo(now()->startOfMinute());

        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'wrongpassword'
            ]);

            $response->assertStatus(401);
        }

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(429);
    }
}