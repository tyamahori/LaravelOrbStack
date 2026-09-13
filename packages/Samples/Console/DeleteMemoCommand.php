<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;
use LaravelOrbStack\Samples\Domain\MemoId;
use LaravelOrbStack\Samples\Domain\MemoNotFound;
use LaravelOrbStack\Samples\UseCase\DeleteMemo;
use Override;

use function is_string;

final class DeleteMemoCommand extends Command
{
    #[Override]
    protected $signature = 'memo:delete
        {id : memo:publish が出力した ID}';

    #[Override]
    protected $description = 'メモを S3、索引、キャッシュから削除する(成功時は何も出力しない)';

    public function handle(DeleteMemo $delete): int
    {
        $raw = $this->argument('id');

        try {
            $delete(new MemoId(is_string($raw) ? $raw : ''));
        } catch (InvalidArgumentException $e) {
            $this->getOutput()->getErrorStyle()->writeln($e->getMessage());

            return self::INVALID;
        } catch (MemoNotFound $e) {
            $this->getOutput()->getErrorStyle()->writeln($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
