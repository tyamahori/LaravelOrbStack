<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');
        Config::get('database');

        $response->assertStatus(200);

        echo getenv('UNIQUE_TEST_TOKEN');

        $this->assertDatabaseMissing(
            'users',
            ['name' => '99']
        );
    }


    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response1(): void
    {
        $response = $this->get('/');
        $item = Config::get('database');

        $response->assertStatus(200);

        echo getenv('UNIQUE_TEST_TOKEN') . 'hohoge';

        $this->assertDatabaseMissing(
            'users',
            ['name' => '200']
        );
    }
}
