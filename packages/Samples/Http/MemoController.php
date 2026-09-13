<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Http;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use LaravelOrbStack\Samples\Domain\MemoId;
use LaravelOrbStack\Samples\Domain\MemoRepository;
use LaravelOrbStack\Samples\UseCase\PublishMemo;
use LaravelOrbStack\Samples\UseCase\ShowMemo;

final readonly class MemoController
{
    private const string SESSION_LAST_PUBLISHED = 'samples.last_published_memo_id';

    public function index(Session $session, MemoRepository $memos, ViewFactory $view): View
    {
        return $view->make('memos.index', [
            'memos' => $memos->all(),
            'lastPublishedId' => $session->get(self::SESSION_LAST_PUBLISHED),
        ]);
    }

    public function store(
        PublishMemoRequest $request,
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
}
