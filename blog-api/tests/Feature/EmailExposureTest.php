<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\User;
use App\Models\Post;

class EmailExposureTest extends TestCase
{
  use RefreshDatabase;
  
  #[Test]
  public function test_user_emails_not_exposed_publicly()
  {
      $user = User::factory()->create(['email' => 'test@example.com']);
      $post = Post::factory()->create(['user_id' => $user->id]);
  
      $response = $this->getJson("/api/posts/{$post->id}");
  
      $response->assertStatus(200)
              ->assertJsonMissing(['email' => 'test@example.com']);
  }
}
