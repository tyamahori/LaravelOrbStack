<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Http;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use LaravelOrbStack\Samples\Domain\MemoId;
use LaravelOrbStack\Samples\Domain\MemoIndex;
use LaravelOrbStack\Samples\UseCase\DeleteMemo;
use LaravelOrbStack\Samples\UseCase\EditMemo;
use LaravelOrbStack\Samples\UseCase\PublishMemo;
use LaravelOrbStack\Samples\UseCase\ShowMemo;

final readonly class MemoController
{
    private const string SESSION_LAST_PUBLISHED = 'samples.last_published_memo_id';

    public function index(Session $session, MemoIndex $index, ViewFactory $view): View
    {
        return $view->make('memos.index', [
            'headings' => $index->latest(),
            'lastPublishedId' => $session->get(self::SESSION_LAST_PUBLISHED),
        ]);
    }

    public function store(
        MemoFormRequest $request,
        PublishMemo $publish,
        Session $session,
        ResponseFactory $response,
    ): RedirectResponse {
        $memo = $publish($request->title(), $request->body());
        $session->put(self::SESSION_LAST_PUBLISHED, $memo->id->value);

        return $response
            ->redirectToRoute('memos.show', ['id' => $memo->id->value])
            ->with('status', 'メモを公開しました');
    }

    public function show(string $id, ShowMemo $show, ViewFactory $view): View
    {
        return $view->make('memos.show', ['memo' => $show(new MemoId($id))]);
    }

    public function edit(string $id, ShowMemo $show, ViewFactory $view): View
    {
        return $view->make('memos.edit', ['memo' => $show(new MemoId($id))]);
    }

    public function update(
        string $id,
        MemoFormRequest $request,
        EditMemo $edit,
        ResponseFactory $response,
    ): RedirectResponse {
        $memo = $edit(new MemoId($id), $request->title(), $request->body());

        return $response
            ->redirectToRoute('memos.show', ['id' => $memo->id->value])
            ->with('status', 'メモを更新しました');
    }

    public function destroy(string $id, DeleteMemo $delete, ResponseFactory $response): RedirectResponse
    {
        $delete(new MemoId($id));

        return $response
            ->redirectToRoute('memos.index')
            ->with('status', 'メモを削除しました');
    }
}
