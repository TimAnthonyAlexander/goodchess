<?php
namespace RealChess;


class TimFish{
    public const WEIGHTS = [
        'P' => 100,
        'N' => 320,
        'B' => 330,
        'R' => 500,
        'Q' => 900,
        'K' => 20000
    ];

    public const RULEWEIGHTS = [
        'check' => 150,
        'checkmate' => 100000,
        'piececount' => 1,
    ];

    public static function countMaterial(Board $board, bool $color): int {
        $material = 0;
        $pieces = $board->getPieces($color);
        foreach ($pieces as $piece) {
            assert($piece instanceof Piece);
            $material += self::WEIGHTS[$piece->getName()] * self::RULEWEIGHTS['piececount'];
        }

        return $material;
    }

    public static function countChecks(Board $board, bool $color = true): int {
        $count = 0;
        $counted = [];

        foreach ($board->anyChecks() as $check) {
            [$checkingPiecePos,] = $check;
            assert($checkingPiecePos instanceof Position);

            $checkingPiece = $board->getPieceFromPosition($checkingPiecePos);
            assert($checkingPiece instanceof Piece);

            if (!in_array($check, $counted, true) && $checkingPiece->getColor()){
                $counted[] = $check;
                $count++;
            }
        }

        return $count;
    }

    public static function evaluate(Board $board, bool $color = true): int {
        $material = self::countMaterial($board, $color);
        $checks = self::countChecks($board, $color);

        return $material + ($checks * self::RULEWEIGHTS['check']);
    }

    public static function evaluateWhiteVsBlack(Board $board): int {
        $white = self::evaluate($board, true);
        $black = self::evaluate($board, false);

        return $white - $black;
    }
}
