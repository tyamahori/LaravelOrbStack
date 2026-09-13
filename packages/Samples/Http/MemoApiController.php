<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Http;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use LaravelOrbStack\Samples\Domain\MemoId;
use LaravelOrbStack\Samples\Domain\MemoIndex;
use LaravelOrbStack\Samples\UseCase\DeleteMemo;
use LaravelOrbStack\Samples\UseCase\EditMemo;
use LaravelOrbStack\Samples\UseCase\PublishMemo;
use LaravelOrbStack\Samples\UseCase\ShowMemo;

/**
 * Same use cases as MemoController, JSON in and out. No session: the
 * "api" middleware group has neither cookies nor CSRF, so clients hold
 * on to the id from the 201 response themselves.
 */
final readonly class MemoApiController
{
    public function index(MemoIndex $index, ResponseFactory $response): JsonResponse
    {
        return $response->json(array_map(MemoJson::heading(...), $index->latest()));
    }

    public function store(
        MemoFormRequest $request,
        PublishMemo $publish,
        ResponseFactory $response,
        UrlGenerator $url,
    ): JsonResponse {
        $memo = $publish($request->title(), $request->body());

        return $response->json(MemoJson::memo($memo), Response::HTTP_CREATED, [
            'Location' => $url->route('api.memos.show', ['id' => $memo->id->value]),
        ]);
    }

    public function show(string $id, ShowMemo $show, ResponseFactory $response): JsonResponse
    {
        return $response->json(MemoJson::memo($show(new MemoId($id))));
    }

    public function update(
        string $id,
        MemoFormRequest $request,
        EditMemo $edit,
        ResponseFactory $response,
    ): JsonResponse {
        return $response->json(MemoJson::memo($edit(new MemoId($id), $request->title(), $request->body())));
    }

    public function destroy(string $id, DeleteMemo $delete, ResponseFactory $response): Response
    {
        $delete(new MemoId($id));

        return $response->noContent();
    }
}
