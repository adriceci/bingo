<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'status',
        'max_number',
        'drawn_numbers',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'drawn_numbers' => 'array',
            'max_number' => 'integer',
        ];
    }

    public function cards()
    {
        return $this->hasMany(BingoCard::class);
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
