<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;
use LaravelOrbStack\Samples\UseCase\PublishMemo;
use Override;

use function is_string;

final class PublishMemoCommand extends Command
{
    #[Override]
    protected $signature = 'memo:publish
        {file : 本文のファイル。- で標準入力から読む}
        {--title= : 見出し。省略時は拡張子を除いたファイル名}
        {--json : 公開したメモを JSON Lines で出力する}';

    #[Override]
    protected $description = 'ファイルの内容をメモとして公開する(標準出力に ID)';

    public function handle(PublishMemo $publish): int
    {
        $file = $this->argument('file');
        $body = match (true) {
            $file === '-' => stream_get_contents(STDIN),
            is_readable($file) => file_get_contents($file),
            default => false,
        };
        if (! is_string($body)) {
            $this->getOutput()->getErrorStyle()->writeln('読み込めません: ' . json_encode($file, JSON_THROW_ON_ERROR));

            return self::INVALID;
        }

        $title = $this->option('title');
        $title = is_string($title) ? $title : pathinfo($file, PATHINFO_FILENAME);

        try {
            $memo = $publish($title, $body);
        } catch (InvalidArgumentException $invalidArgumentException) {
            $this->getOutput()->getErrorStyle()->writeln($invalidArgumentException->getMessage());

            return self::INVALID;
        }

        $this->line($this->option('json') === true ? MemoJsonLine::of($memo) : $memo->id->value);

        return self::SUCCESS;
    }
}
