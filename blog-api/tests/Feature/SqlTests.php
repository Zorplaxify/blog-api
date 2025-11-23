<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SqlTests extends TestCase
{
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