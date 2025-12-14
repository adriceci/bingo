<?php

namespace Tests\Unit;

use App\Http\Controllers\BingoController;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class BingoGridUniformityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * This test samples many generated grids and asserts that:
     * - Each grid has exactly 15 numbers (5 per row, 3 rows, 9 columns).
     * - Each row has exactly 5 numbers.
     * - Column counts vary by design (0-3 per column) but never exceed the row count.
     * - Values are uniformly distributed within their column ranges.
     */
    public function test_generate_grid_uniformity(): void
    {
        // Prepare controller instance via the container
        /** @var BingoController $controller */
        $controller = app(BingoController::class);

        // Access private constants via reflection
        $ref = new ReflectionClass($controller);
        $method = $ref->getMethod('generateGrid');
        $method->setAccessible(true);

        $gridRows = $ref->getConstant('GRID_ROWS'); // expected 3
        $gridCols = $ref->getConstant('GRID_COLUMNS'); // expected 9 (columns 0-8: ranges 1-90)

        $samples = 5000;

        // Track value frequencies per column
        $valueFreq = [];
        for ($c = 0; $c < $gridCols; $c++) {
            $start = $c === 0 ? 1 : ($c * 10);
            $end = $c === 0 ? 9 : ($c === $gridCols - 1 ? 90 : (($c * 10) + 9));
            $valueFreq[$c] = array_fill($start, ($end - $start + 1), 0);
        }

        for ($i = 0; $i < $samples; $i++) {
            /** @var array<int, array<int, int|null>> $grid */
            $grid = $method->invoke($controller);

            // Assert structure: 3 rows, 9 columns
            $this->assertCount($gridRows, $grid);
            foreach ($grid as $row) {
                $this->assertCount($gridCols, $row);
            }

            // Count numbers per row and per column
            $colCounts = array_fill(0, $gridCols, 0);
            $totalNumbers = 0;

            for ($r = 0; $r < $gridRows; $r++) {
                for ($c = 0; $c < $gridCols; $c++) {
                    $val = $grid[$r][$c];
                    if ($val !== null) {
                        $totalNumbers++;
                        $colCounts[$c]++;
                        $valueFreq[$c][$val]++;
                    }
                }
            }

            // Assert exactly 15 numbers per grid
            $this->assertEquals(15, $totalNumbers, "Grid {$i} should have exactly 15 numbers");

            // Assert exactly 5 numbers per row
            for ($r = 0; $r < $gridRows; $r++) {
                $count = 0;
                for ($c = 0; $c < $gridCols; $c++) {
                    if ($grid[$r][$c] !== null) {
                        $count++;
                    }
                }
                $this->assertEquals(5, $count, "Grid {$i} row {$r} should have exactly 5 numbers");
            }

            // Assert column counts are within allowed bounds (0 to GRID_ROWS) and sum to 15
            $this->assertEquals(15, array_sum($colCounts), "Grid {$i} should have 15 numbers across columns");
            for ($c = 0; $c < $gridCols; $c++) {
                $this->assertTrue(
                    $colCounts[$c] >= 0 && $colCounts[$c] <= $gridRows,
                    "Grid {$i} column {$c} count {$colCounts[$c]} outside [0, {$gridRows}]"
                );
            }
        }

        // Assert value frequencies within each column range are roughly uniform
        $toleranceRatio = 0.20;
        foreach ($valueFreq as $c => $freqs) {
            $totalPicks = array_sum($freqs);
            $numValues = count($freqs);
            if ($totalPicks === 0) {
                $this->fail("Column {$c} had zero picks");
            }
            $expected = $totalPicks / $numValues;
            $min = (int) floor($expected * (1 - $toleranceRatio));
            $max = (int) ceil($expected * (1 + $toleranceRatio));
            foreach ($freqs as $val => $count) {
                $this->assertTrue(
                    $count >= $min && $count <= $max,
                    "Column {$c} value {$val} observed {$count} outside [{$min}, {$max}]"
                );
            }
        }
    }
}
