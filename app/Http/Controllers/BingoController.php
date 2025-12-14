<?php

namespace App\Http\Controllers;

use App\Events\CardsGenerated;
use App\Events\GameClosed;
use App\Events\GameReset;
use App\Events\GameStarted;
use App\Events\NumberDrawn;
use App\Models\BingoCard;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BingoController extends Controller
{
    private const MAX_ACTIVE_CARDS = 100;
    private const GRID_ROWS = 3;
    private const GRID_COLUMNS = 9;
    private const MAX_ATTEMPTS_PER_CARD = 25;

    public function create(Request $request)
    {
        return $this->store($request);
    }

    public function store(Request $request)
    {
        $userId = $request->user()?->id ?: null;

        $game = Game::create([
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'status' => Game::STATUS_ACTIVE,
            'max_number' => 90,
            'drawn_numbers' => [],
        ]);

        broadcast(new GameStarted($game))->toOthers();

        return redirect()->route('bingo.show', $game);
    }

    public function show(Request $request, Game $game): Response
    {
        $userId = $request->user()->id;
        $game->load([
            'cards' => fn($query) => $query
                ->where('user_id', $userId)
                ->where('archived', false)
                ->orderBy('card_number')
        ]);

        return Inertia::render('Bingo/Index', [
            'game' => [
                'id' => $game->id,
                'status' => $game->status,
                'drawnNumbers' => $game->drawn_numbers ?? [],
                'maxNumber' => $game->max_number,
            ],
            'cards' => $game->cards
                ->map(fn(BingoCard $card) => [
                    'id' => $card->id,
                    'cardNumber' => $card->card_number,
                    'numbersGrid' => $card->numbers_grid,
                ]),
            'archivedCount' => $game->cards()->where('user_id', $userId)->where('archived', true)->count(),
            'maxActiveCards' => self::MAX_ACTIVE_CARDS,
        ]);
    }

    public function activate(Game $game): JsonResponse
    {
        if ($game->isClosed()) {
            return $this->error('La partida está cerrada y no se puede reactivar.');
        }

        if (! $game->isActive()) {
            $game->status = Game::STATUS_ACTIVE;
            $game->save();
            broadcast(new GameStarted($game))->toOthers();
        }

        return response()->json($this->gamePayload($game));
    }

    public function draw(Game $game): JsonResponse
    {
        if ($game->isClosed()) {
            return $this->error('La partida está cerrada.');
        }

        $drawn = $game->drawn_numbers ?? [];
        if (count($drawn) >= $game->max_number) {
            return $this->error('No quedan números disponibles.');
        }

        $available = array_values(array_diff(range(1, $game->max_number), $drawn));
        $nextNumber = $available[random_int(0, count($available) - 1)];
        $drawn[] = $nextNumber;
        sort($drawn);

        $game->update([
            'drawn_numbers' => $drawn,
            'status' => Game::STATUS_ACTIVE,
        ]);

        broadcast(new NumberDrawn($game, $nextNumber, $drawn))->toOthers();

        return response()->json([
            'number' => $nextNumber,
            'game' => $this->gamePayload($game),
        ]);
    }

    public function generateCards(Request $request, Game $game): JsonResponse
    {
        if ($game->isClosed()) {
            return $this->error('La partida está cerrada.');
        }

        $data = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:' . self::MAX_ACTIVE_CARDS],
        ]);

        $requested = (int) $data['count'];
        $userId = $request->user()->id;
        $activeCount = $game->cards()->where('user_id', $userId)->where('archived', false)->count();
        $maxCardsPerUser = 5;
        if ($activeCount + $requested > $maxCardsPerUser) {
            return $this->error('Supera el límite de ' . $maxCardsPerUser . ' tableros activos por usuario.');
        }

        $newCards = collect();
        $hashes = [];
        $nextCardNumber = (int) $game->cards()->max('card_number') + 1;

        try {
            DB::transaction(function () use ($game, $requested, $userId, &$newCards, &$nextCardNumber, &$hashes) {
                for ($i = 0; $i < $requested; $i++) {
                    [$grid, $hash] = $this->uniqueGridForGame($game, $hashes);
                    $hashes[] = $hash;

                    $card = new BingoCard([
                        'id' => (string) Str::uuid(),
                        'user_id' => $userId,
                        'card_number' => $nextCardNumber + $i,
                        'numbers_grid' => $grid,
                        'grid_hash' => $hash,
                        'archived' => false,
                    ]);

                    $newCards->push($card);
                }

                $game->cards()->saveMany($newCards);
            });
        } catch (\RuntimeException $exception) {
            return $this->error($exception->getMessage(), 409);
        }

        broadcast(new CardsGenerated($game, $newCards->values()->all()))->toOthers();

        return response()->json([
            'cards' => $newCards->map(fn(BingoCard $card) => [
                'id' => $card->id,
                'cardNumber' => $card->card_number,
                'numbersGrid' => $card->numbers_grid,
            ])->values(),
            'activeCount' => $activeCount + $newCards->count(),
            'game' => $this->gamePayload($game),
        ]);
    }

    public function reset(Game $game): JsonResponse
    {
        if ($game->isClosed()) {
            return $this->error('La partida está cerrada.');
        }

        DB::transaction(function () use ($game) {
            $game->update([
                'drawn_numbers' => [],
                'status' => Game::STATUS_ACTIVE,
            ]);

            $game->cards()->where('archived', false)->update(['archived' => true]);
        });

        $archivedCount = $game->cards()->count();

        broadcast(new GameReset($game, $archivedCount))->toOthers();

        return response()->json([
            'game' => $this->gamePayload($game),
            'archivedCount' => $archivedCount,
            'cards' => [],
        ]);
    }

    public function close(Game $game): JsonResponse
    {
        if ($game->isClosed()) {
            return $this->error('La partida ya está cerrada.');
        }

        $game->update(['status' => Game::STATUS_CLOSED]);

        broadcast(new GameClosed($game))->toOthers();

        return response()->json($this->gamePayload($game));
    }

    private function error(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }

    /**
     * Generate a bingo card with 3 rows, 9 columns, and exactly 5 numbers per row (15 total).
     * Each column corresponds to a range: 1-9, 10-19, ..., 80-90.
     * Each row has exactly 5 numbers in random columns, with 4 blanks.
     * Numbers in each column are sorted in ascending order without alterar la cantidad por fila.
     * 
     * @return array<int, array<int, int|null>>
     */
    private function generateGrid(): array
    {
        $rows = array_fill(0, self::GRID_ROWS, array_fill(0, self::GRID_COLUMNS, null));
        $usedNumbers = [];
        $positionsByColumn = array_fill(0, self::GRID_COLUMNS, []);

        // Paso 1: elegir 5 columnas por fila y registrar las posiciones
        for ($row = 0; $row < self::GRID_ROWS; $row++) {
            $columns = range(0, self::GRID_COLUMNS - 1);
            shuffle($columns);
            $selectedColumns = array_slice($columns, 0, 5);
            sort($selectedColumns);

            foreach ($selectedColumns as $column) {
                $positionsByColumn[$column][] = $row;
            }
        }

        // Paso 2: para cada columna, asignar números ordenados ascendentes en las filas seleccionadas
        foreach ($positionsByColumn as $column => $rowsWithColumn) {
            if (empty($rowsWithColumn)) {
                continue;
            }

            sort($rowsWithColumn);

            $rangeStart = $column === 0 ? 1 : ($column * 10);
            $rangeEnd = $column === 0 ? 9 : ($column === self::GRID_COLUMNS - 1 ? 90 : (($column * 10) + 9));

            $available = [];
            for ($num = $rangeStart; $num <= $rangeEnd; $num++) {
                if (!in_array($num, $usedNumbers, true)) {
                    $available[] = $num;
                }
            }

            if (count($available) < count($rowsWithColumn)) {
                throw new \RuntimeException('No hay números suficientes para completar la columna.');
            }

            shuffle($available);
            $selectedNumbers = array_slice($available, 0, count($rowsWithColumn));
            sort($selectedNumbers);

            foreach ($rowsWithColumn as $index => $rowIndex) {
                $rows[$rowIndex][$column] = $selectedNumbers[$index];
                $usedNumbers[] = $selectedNumbers[$index];
            }
        }

        return $rows;
    }

    /**
     * @param array<int, array<int, int|null>> $grid
     * @return array<int, array<int, int|null>>
     */
    private function normalizeGrid(array $grid): array
    {
        $normalized = [];

        foreach ($grid as $row) {
            $normalized[] = array_values(array_map(static function ($value) {
                return $value === null ? null : (int) $value;
            }, array_pad($row, self::GRID_COLUMNS, null)));
        }

        return array_slice($normalized, 0, self::GRID_ROWS);
    }

    /**
     * @param array<int, array<int, int|null>> $grid
     */
    private function gridHash(array $grid): string
    {
        return hash('sha256', json_encode($this->normalizeGrid($grid), JSON_THROW_ON_ERROR));
    }

    private function uniqueGridForGame(Game $game, array $blockedHashes = []): array
    {
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS_PER_CARD; $attempt++) {
            $grid = $this->generateGrid();
            $hash = $this->gridHash($grid);

            if (in_array($hash, $blockedHashes, true)) {
                continue;
            }

            $exists = $game->cards()
                ->where('grid_hash', $hash)
                ->exists();

            if (! $exists) {
                return [$this->normalizeGrid($grid), $hash];
            }
        }

        throw new \RuntimeException('No se pudo generar un tablero único tras varios intentos.');
    }

    public function history(Request $request): Response
    {
        $games = Game::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->with(['cards' => fn($query) => $query->selectRaw('game_id, count(*) as total_cards')->groupBy('game_id')])
            ->paginate(15);

        return Inertia::render('Bingo/History', [
            'games' => $games,
        ]);
    }

    private function gamePayload(Game $game): array
    {
        return [
            'id' => $game->id,
            'status' => $game->status,
            'drawnNumbers' => $game->drawn_numbers ?? [],
            'maxNumber' => $game->max_number,
        ];
    }
}
