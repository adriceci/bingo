<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['web', 'auth']]);

Broadcast::channel('bingo-game.{gameId}', function ($user, string $gameId) {
    if (! $user) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});
