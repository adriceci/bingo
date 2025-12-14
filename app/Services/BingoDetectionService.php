<?php

namespace App\Services;

use App\Models\BingoCard;

class BingoDetectionService
{
    /**
     * Detecta si un tablero tiene una línea completa
     * Una línea es una fila completa de números que han sido sacados
     *
     * @param BingoCard $card
     * @param array<int> $drawnNumbers
     * @return bool
     */
    public static function hasCompletedLine(BingoCard $card, array $drawnNumbers): bool
    {
        $grid = $card->numbers_grid;
        
        foreach ($grid as $row) {
            $rowNumbers = array_filter($row, fn($num) => $num !== null);
            
            if (empty($rowNumbers)) {
                continue;
            }
            
            // Verificar si todos los números de la fila están en los números sacados
            if (static::allNumbersDrawn($rowNumbers, $drawnNumbers)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Detecta si un tablero completo tiene bingo (todos los números)
     *
     * @param BingoCard $card
     * @param array<int> $drawnNumbers
     * @return bool
     */
    public static function hasBingo(BingoCard $card, array $drawnNumbers): bool
    {
        $grid = $card->numbers_grid;
        $allNumbers = [];
        
        foreach ($grid as $row) {
            foreach ($row as $number) {
                if ($number !== null) {
                    $allNumbers[] = $number;
                }
            }
        }
        
        if (empty($allNumbers)) {
            return false;
        }
        
        return static::allNumbersDrawn($allNumbers, $drawnNumbers);
    }

    /**
     * Verifica si todos los números en el array están en los números sacados
     *
     * @param array<int> $numbers
     * @param array<int> $drawnNumbers
     * @return bool
     */
    private static function allNumbersDrawn(array $numbers, array $drawnNumbers): bool
    {
        $drawnSet = array_flip($drawnNumbers);
        
        foreach ($numbers as $number) {
            if (!isset($drawnSet[$number])) {
                return false;
            }
        }
        
        return true;
    }
}
