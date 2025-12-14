<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BingoCard extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'game_id',
        'user_id',
        'card_number',
        'numbers_grid',
        'grid_hash',
        'archived',
        'has_line',
        'winner',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'numbers_grid' => 'array',
            'archived' => 'boolean',
            'card_number' => 'integer',
        ];
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
