<?php

namespace App\Events;

use App\Models\Game;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NumberDrawn implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param array<int, int> $drawnNumbers
     */
    public function __construct(public Game $game, public int $number, public array $drawnNumbers) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('bingo-game.' . $this->game->id)];
    }

    public function broadcastAs(): string
    {
        return 'NumberDrawn';
    }

    public function broadcastWith(): array
    {
        return [
            'gameId' => $this->game->id,
            'number' => $this->number,
            'drawnNumbers' => $this->drawnNumbers,
        ];
    }
}
