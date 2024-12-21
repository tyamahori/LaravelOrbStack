<?php

declare(strict_types=1);

namespace App\Console\Commands\Sample;

use Carbon\CarbonImmutable;
use Illuminate\Cache\CacheManager;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Factory as FactoryContract;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Mail\MailManager;
use Illuminate\Session\Store;
use Psr\SimpleCache\InvalidArgumentException;
use RuntimeException;

use function sprintf;

class AppHealthCheck extends Command
{
    public function __construct(
        private readonly ConnectionInterface $connectionInterface,
        private readonly FactoryContract $factoryContract,
        private readonly CacheManager $cacheManager,
        private readonly Store $store,
        private readonly MailManager $mailManager,
    ) {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @inheritdoc
     */
    protected $signature = 'app:sample-health-check';

    /**
     * The console command description.
     *
     * @inheritdoc
     */
    protected $description = 'Command description';

    /**
     * @throws InvalidArgumentException
     */
    public function handle(): int
    {
        $this->updateS3();
        $this->database();
        $this->useCache($this->cacheManager);
        $this->useSession($this->store);
        $this->useMailManager($this->mailManager);

        return self::SUCCESS;
    }

    private function database(): void
    {
        $this->connectionInterface->table('migrations')->select('*')->get()->toArray();
    }

    private function updateS3(): void
    {
        $now = CarbonImmutable::now();

        $fileName = sprintf('file-%s.csv', $now->toAtomString());

        $this->factoryContract->disk('s3')->put($fileName, 'Contents');
    }

    /**
     * @throws InvalidArgumentException
     */
    private function useCache(CacheManager $cm): void
    {
        $key = CarbonImmutable::now()->toAtomString();
        $item = 'asdfasdf';
        if (!$cm->set($key, $item)) {
            throw new RuntimeException();
        }
    }

    private function useSession(Store $sm): void
    {
        $key = CarbonImmutable::now()->toAtomString();
        $item = 'asdfasdf';
        $sm->put($key, $item);
    }

    private function useMailManager(MailManager $mm): void
    {
        $mm->to('sample@example.com')
            ->send(new \App\Mail\AppHealthCheck());
    }
}
