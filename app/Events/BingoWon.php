<?php

namespace App\Events;

use App\Models\Game;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BingoWon implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Game $game,
        public int $userId,
        public string $cardId,
        public int $cardNumber,
    ) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('bingo-game.' . $this->game->id)];
    }

    public function broadcastAs(): string
    {
        return 'BingoWon';
    }

    public function broadcastWith(): array
    {
        return [
            'gameId' => $this->game->id,
            'userId' => $this->userId,
            'cardId' => $this->cardId,
            'cardNumber' => $this->cardNumber,
            'status' => 'closed',
        ];
    }
}
