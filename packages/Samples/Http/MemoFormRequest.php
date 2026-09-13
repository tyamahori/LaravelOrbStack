<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Http;

use Illuminate\Foundation\Http\FormRequest;
use LaravelOrbStack\Samples\Domain\Memo;

final class PublishMemoRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:' . Memo::TITLE_MAX_LENGTH],
            'body' => ['required', 'string', 'max:10000'],
        ];
    }

    public function title(): string
    {
        return $this->string('title')->toString();
    }

    public function body(): string
    {
        return $this->string('body')->toString();
    }
}
