<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Test;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Routing\UrlGenerator;
use LaravelOrbStack\Samples\Http\Web\HomeController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Tests\TestCase;

#[CoversClass(HomeController::class)]
#[UsesNamespace('LaravelOrbStack')]
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
