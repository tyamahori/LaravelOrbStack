<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;
use JsonException;
use LaravelOrbStack\Samples\Domain\MemoId;
use LaravelOrbStack\Samples\Domain\MemoNotFound;
use LaravelOrbStack\Samples\UseCase\ShowMemo;
use Override;

final class ShowMemoCommand extends Command
{
    #[Override]
    protected $signature = 'memo:show
        {id : memo:publish が出力した ID}
        {--json : メモを JSON Lines で出力する}';

    #[Override]
    protected $description = 'メモを表示する(キャッシュ経由で S3 から読む)';

    public function handle(ShowMemo $show): int
    {
        $raw = $this->argument('id');

        try {
            $memo = $show(new MemoId($raw));
            $json = $this->option('json') === true ? MemoJsonLine::of($memo) : null;
        } catch (InvalidArgumentException $e) {
            $this->getOutput()
                ->getErrorStyle()
                ->writeln($e->getMessage());

            return self::INVALID;
        } catch (JsonException|MemoNotFound $e) {
            $this->getOutput()
                ->getErrorStyle()
                ->writeln($e->getMessage());

            return self::FAILURE;
        }

        if ($json !== null) {
            $this->line($json);

            return self::SUCCESS;
        }

        $this->line($memo->title);
        $this->line('');
        $this->line($memo->body);

        return self::SUCCESS;
    }
}
