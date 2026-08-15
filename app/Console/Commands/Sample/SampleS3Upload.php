<?php

declare(strict_types=1);

namespace App\Console\Commands\Sample;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Factory as FactoryContract;
use Override;

use function sprintf;

class SampleS3Upload extends Command
{
    public function __construct(
        private readonly FactoryContract $filesystem
    ) {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @inheritdoc
     */
    #[Override]
    protected $signature = 'app:sample-s3-upload';

    /**
     * The console command description.
     *
     * @inheritdoc
     */
    #[Override]
    protected $description = 'Command description';

    public function handle(): int
    {
        $now = CarbonImmutable::now();

        $fileName = sprintf('file-%s.csv', $now->toAtomString());

        $this->filesystem->disk('s3')->put($fileName, 'Contents');

        return self::SUCCESS;
    }
}
