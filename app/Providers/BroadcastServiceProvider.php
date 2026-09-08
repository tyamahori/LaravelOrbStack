<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\ServiceProvider;

use function assert;

final class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(BroadcastManager $broadcast): void
    {
        $broadcast->routes();
        $broadcaster = $broadcast->driver();
        assert($broadcaster instanceof Broadcaster, 'Every built-in broadcast driver extends Broadcaster.');

        require $this->app->basePath('routes/channels.php');
    }
}
