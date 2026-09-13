<?php

declare(strict_types=1);

use Illuminate\Routing\Router;
use LaravelOrbStack\Samples\Domain\MemoId;
use LaravelOrbStack\Samples\Http\MemoApiController;

/**
 * Listed in bootstrap/app.php withRouting(api: ...), so the "api" middleware
 * group applies and every path is prefixed with /api.
 *
 * @var Router $router
 */
$router->get('/memos', [MemoApiController::class, 'index'])->name('api.memos.index');
$router->post('/memos', [MemoApiController::class, 'store'])->name('api.memos.store');
$router->get('/memos/{id}', [MemoApiController::class, 'show'])->name('api.memos.show')->where('id', MemoId::PATTERN);
$router->put('/memos/{id}', [MemoApiController::class, 'update'])->name('api.memos.update')->where('id', MemoId::PATTERN);
$router->delete('/memos/{id}', [MemoApiController::class, 'destroy'])->name('api.memos.destroy')->where('id', MemoId::PATTERN);
