<?php

namespace App\Events;

use App\Models\BingoCard;
use App\Models\Game;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CardsGenerated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param array<int, \App\Models\BingoCard> $cards
     */
    public function __construct(public Game $game, public array $cards) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('bingo-game.' . $this->game->id)];
    }

    public function broadcastAs(): string
    {
        return 'CardsGenerated';
    }

    public function broadcastWith(): array
    {
        return [
            'gameId' => $this->game->id,
            'cards' => collect($this->cards)->map(function (BingoCard $card) {
                return [
                    'id' => $card->id,
                    'userId' => $card->user_id,
                    'cardNumber' => $card->card_number,
                    'numbersGrid' => $card->numbers_grid,
                ];
            })->values()->all(),
        ];
    }
}
