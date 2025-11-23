<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\User;

class TokenPruningTest extends TestCase
{
  use RefreshDatabase;

  #[Test]
  public function test_expired_tokens_are_pruned()
  {
    $user = User::factory()->create([
      'email' => 'token_pruning_test_' . uniqid() . '@example.com'
    ]);

    $expiredToken = $user->createToken('test', [
      'posts:read',
      'posts:write-own'
    ], now()->subHours(25));

    $validToken = $user->createToken('test2', [
      'posts:read',
      'posts:write-own'
    ], now()->addHours(2));

    $this->artisan('sanctum:prune-expired', ['--hours' => 24])
      ->assertExitCode(0);

    $this->assertDatabaseMissing('personal_access_tokens', [
      'id' => $expiredToken->accessToken->id
    ]);

    $this->assertDatabaseHas('personal_access_tokens', [
      'id' => $validToken->accessToken->id
    ]);
  }
}
