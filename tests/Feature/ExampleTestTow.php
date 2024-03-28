<?php
declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ExampleTestTow extends TestCase
{
    /**
     * A basic test example.
     */
    #[Test]
    public function the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        $this->assertDatabaseMissing(
            'users',
            ['name' => '99']
        );
    }

    /**
     * A basic test example.
     */
    #[Test]
    public function the_application_returns_a_successful_response1(): void
    {
        $response = $this->get('/');
        Config::get('database');

        $response->assertStatus(200);

        $this->assertDatabaseEmpty('users');
    }
}
