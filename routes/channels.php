<?php

declare(strict_types=1);

use App\Models\User;

/** @var Illuminate\Broadcasting\Broadcasters\Broadcaster $broadcaster */

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The channel authorization callbacks are used to
| check if an authenticated user can listen to the channel.
|
*/

$broadcaster->channel('App.Models.User.{id}', static fn (User $user, string $id): bool => $user->id === (int) $id);
