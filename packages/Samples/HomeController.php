<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Routing\UrlGenerator;
use Psr\Log\LoggerInterface;

use function assert;
use function is_string;

class HomeController extends Controller
{
    public function home(
        Repository $config,
        UrlGenerator $url,
        CarbonImmutable $carbonImmutable,
        LoggerInterface $logger,
        Factory $view
    ): Renderable {

        $appUrl = $config->get('app.url');
        assert(is_string($appUrl), 'appUrl should be string.');
        $route = $url->route('welcome');

        $logger
            ->info(
                'debug',
                [
                    $appUrl,
                    $route,
                    $carbonImmutable->toString(),
                ]
            );

        return $view->make('welcome');
    }
}
