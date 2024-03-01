<?php
declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * A basic test example.
     */
    #[Test]
    final public function サンプルメソッド(): void
    {
        $u = 9999;

        var_dump($u);
        $this->get('/')->assertStatus(200);
    }
}
