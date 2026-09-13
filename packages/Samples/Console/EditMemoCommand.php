<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;
use LaravelOrbStack\Samples\Domain\MemoId;
use LaravelOrbStack\Samples\Domain\MemoNotFound;
use LaravelOrbStack\Samples\UseCase\EditMemo;
use Override;

use function is_string;

final class EditMemoCommand extends Command
{
    #[Override]
    protected $signature = 'memo:edit
        {id : memo:publish が出力した ID}
        {file : 新しい本文のファイル。- で標準入力から読む}
        {--title= : 新しい見出し。省略時は拡張子を除いたファイル名}
        {--json : 更新したメモを JSON Lines で出力する}';

    #[Override]
    protected $description = 'メモの見出しと本文を書き換える(標準出力に ID)';

    public function handle(EditMemo $edit): int
    {
        $raw = $this->argument('id');
        $file = $this->argument('file');
        $body = match (true) {
            ! is_string($file) => false,
            $file === '-' => stream_get_contents(STDIN),
            is_readable($file) => file_get_contents($file),
            default => false,
        };
        if (! is_string($file) || ! is_string($body)) {
            $this->getOutput()->getErrorStyle()->writeln('読み込めません: ' . json_encode($file, JSON_THROW_ON_ERROR));

            return self::INVALID;
        }

        $title = $this->option('title');
        $title = is_string($title) ? $title : pathinfo($file, PATHINFO_FILENAME);

        try {
            $memo = $edit(new MemoId(is_string($raw) ? $raw : ''), $title, $body);
        } catch (InvalidArgumentException $e) {
            $this->getOutput()->getErrorStyle()->writeln($e->getMessage());

            return self::INVALID;
        } catch (MemoNotFound $e) {
            $this->getOutput()->getErrorStyle()->writeln($e->getMessage());

            return self::FAILURE;
        }

        $this->line($this->option('json') === true ? MemoJsonLine::of($memo) : $memo->id->value);

        return self::SUCCESS;
    }
}
