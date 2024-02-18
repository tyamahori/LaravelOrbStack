<?php
declare(strict_types=1);

namespace Tests\Feature;

use Override;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use DatabaseTransactions;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        echo shell_exec('php artisan -V');
    }

    /**
     * A basic test example.
     *
     * @test
     */
    public function the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');
        Config::get('database');
        $response->assertStatus(200);
        $this->assertDatabaseMissing(
            'users',
            ['name' => '99']
        );
    }

    /**
     * A basic test example.
     *
     * @test
     */
    public function the_application_returns_a_successful_response1(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $this->assertDatabaseMissing(
            'users',
            ['name' => '200']
        );
    }
}
