<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Http\Api;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use JsonException;
use LaravelOrbStack\Samples\Domain\MemoId;
use LaravelOrbStack\Samples\Domain\MemoIndex;
use LaravelOrbStack\Samples\Domain\MemoNotFound;
use LaravelOrbStack\Samples\Http\MemoFormRequest;
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

    /**
     * @throws JsonException
     */
    public function store(
        MemoFormRequest $request,
        PublishMemo $publish,
        ResponseFactory $response,
        UrlGenerator $url,
    ): JsonResponse {
        $memo = $publish($request->title(), $request->body());

        return $response->json(MemoJson::memo($memo), Response::HTTP_CREATED, [
            'Location' => $url->route('api.memos.show', [
                'id' => $memo->id->value,
            ]),
        ]);
    }

    /**
     * @throws JsonException
     * @throws MemoNotFound
     */
    public function show(string $id, ShowMemo $show, ResponseFactory $response): JsonResponse
    {
        return $response->json(MemoJson::memo($show(new MemoId($id))));
    }

    /**
     * @throws JsonException
     * @throws MemoNotFound
     */
    public function update(
        string $id,
        MemoFormRequest $request,
        EditMemo $edit,
        ResponseFactory $response,
    ): JsonResponse {
        return $response->json(MemoJson::memo($edit(new MemoId($id), $request->title(), $request->body())));
    }

    /**
     * @throws JsonException
     * @throws MemoNotFound
     */
    public function destroy(string $id, DeleteMemo $delete, ResponseFactory $response): Response
    {
        $delete(new MemoId($id));

        return $response->noContent();
    }
}
