<?php

declare(strict_types=1);

use Illuminate\Http\Request;

/** @var Illuminate\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

$router->get(
    '/user',
    static fn (Request $request) => $request->user(),
)->middleware('auth');
