<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Override;

final class InspireCommand extends Command
{
    #[Override]
    protected $signature = 'inspire';

    #[Override]
    protected $description = 'Display an inspiring quote';

    public function handle(): void
    {
        $this->comment(Inspiring::quote());
    }
}
