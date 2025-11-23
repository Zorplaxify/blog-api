<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_register_with_valid_data()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'token'
            ]);
    }

    #[Test]
    public function registration_fails_with_invalid_email()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'error',
                'messages' => ['email']
            ]);
    }

    #[Test]
    public function registration_fails_with_duplicate_email()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'error',
                'messages' => ['email']
            ]);
    }

    #[Test]
    public function user_can_login_with_correct_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test' . uniqid() . '@example.com',
            'password' => Hash::make('password') 
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password' 
        ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function login_fails_with_incorrect_password()
    {
        $user = User::factory()->create([
            'email' => 'test' . uniqid() . '@example.com',
            'password' => Hash::make('correct_password')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test' . uniqid() . '@example.com',
            'password' => 'wrong_password'
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'The provided credentials are incorrect.']);
    }

    #[Test]
    public function test_strong_password_requirements()
    {
        $weakPasswords = [
            '123456',
            'password',
            'abc123',
            '12345678',
            'ABC123',
            'Password',
        ];

        foreach ($weakPasswords as $password) {
            $response = $this->postJson('/api/auth/register', [
                'name' => 'Test User',
                'email' => 'test' . uniqid() . '@example.com',
                'password' => $password,
                'password_confirmation' => $password
            ]);

            $response->assertStatus(422);

            $responseData = $response->json();
            if (isset($responseData['messages']['password'])) {
                $this->assertNotEmpty($responseData['messages']['password']);
            } else {
                $response->assertJsonValidationErrors(['password']);
            }
        }
        $this->travelTo(now()->addHour());

        // Test strong password
        $strongResponse = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'strong@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ]);
        $strongResponse->assertStatus(201);
    }

    #[Test]
    public function login_fails_with_extra_fields()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test' . uniqid() . '@example.com',
            'password' => 'password123',
            'invalid_field' => 'should_fail'
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['error', 'invalid_fields']);
    }

    #[Test]
    public function authenticated_user_can_logout()
    {
        $user = User::factory()->create();
        
        $token = $user->createToken('test-token', [
            'posts:read',
            'posts:write-own'
        ], now()->addDays(1))->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Successfully logged out']);

        $this->assertCount(0, $user->tokens);
    }

    #[Test]
    public function unauthenticated_user_cannot_logout()
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }
}