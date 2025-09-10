<?php

namespace Tests\Feature;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SimpleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function basic_test()
    {
        $this->assertTrue(true);
    }

    #[Test]
    public function can_access_home_page()
    {
    $response = $this->get('/');
    $response->assertStatus(302);
    }
} 