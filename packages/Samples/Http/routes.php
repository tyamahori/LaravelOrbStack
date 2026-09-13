<?php

declare(strict_types=1);

use Illuminate\Routing\Router;
use LaravelOrbStack\Samples\Domain\MemoId;
use LaravelOrbStack\Samples\Http\HomeController;
use LaravelOrbStack\Samples\Http\MemoController;

/**
 * Listed in bootstrap/app.php withRouting(web: ...), so the "web" middleware group applies.
 *
 * @var Router $router
 */
$router->get('/', [HomeController::class, 'home'])->name('welcome');

$router->get('/memos', [MemoController::class, 'index'])->name('memos.index');
$router->post('/memos', [MemoController::class, 'store'])->name('memos.store');
$router->get('/memos/{id}', [MemoController::class, 'show'])->name('memos.show')->where('id', MemoId::PATTERN);
$router->get('/memos/{id}/edit', [MemoController::class, 'edit'])->name('memos.edit')->where('id', MemoId::PATTERN);
$router->put('/memos/{id}', [MemoController::class, 'update'])->name('memos.update')->where('id', MemoId::PATTERN);
$router->delete('/memos/{id}', [MemoController::class, 'destroy'])->name('memos.destroy')->where('id', MemoId::PATTERN);
