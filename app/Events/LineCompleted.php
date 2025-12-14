<?php

namespace App\Events;

use App\Models\BingoCard;
use App\Models\Game;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LineCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Game $game,
        public BingoCard $card,
        public int $userId,
    ) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('bingo-game.' . $this->game->id)];
    }

    public function broadcastAs(): string
    {
        return 'LineCompleted';
    }

    public function broadcastWith(): array
    {
        return [
            'gameId' => $this->game->id,
            'cardId' => $this->card->id,
            'cardNumber' => $this->card->card_number,
            'userId' => $this->userId,
        ];
    }
}
