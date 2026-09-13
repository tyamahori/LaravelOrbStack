<?php

declare(strict_types=1);

use LaravelOrbStack\Samples\Http\HomeController;

/** @var Illuminate\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

$router->get('/', [HomeController::class, 'home'])->name('welcome');
