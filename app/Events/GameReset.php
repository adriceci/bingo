<?php

namespace App\Events;

use App\Models\Game;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameReset implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Game $game, public int $archivedCount) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('bingo-game.' . $this->game->id)];
    }

    public function broadcastAs(): string
    {
        return 'GameReset';
    }

    public function broadcastWith(): array
    {
        return [
            'gameId' => $this->game->id,
            'drawnNumbers' => [],
            'archivedCount' => $this->archivedCount,
            'status' => $this->game->status,
        ];
    }
}
