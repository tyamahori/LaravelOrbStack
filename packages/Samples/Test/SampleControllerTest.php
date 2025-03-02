<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Test;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SampleControllerTest extends TestCase
{
    #[Test]
    public function エンドポイントにアクセスすると200になる(): void
    {
        $response = $this->get(route('welcome'));
        $response->assertStatus(200);
    }
}
