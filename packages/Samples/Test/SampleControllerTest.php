<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Test;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Routing\UrlGenerator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SampleControllerTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    #[Test]
    public function エンドポイントにアクセスすると200になる(): void
    {
        $response = $this->get($this->app->make(UrlGenerator::class)->route('welcome'));
        $response->assertOk();
    }
}
