<?php
namespace RealChess;


use Exception;

class TimFish {

    private const VALUE = [
        'P' => 1,
        'N' => 3.2,
        'B' => 3.3,
        'R' => 5,
        'Q' => 9,
        'K' => 200,
    ];

    /**
     * @throws Exception
     */
    public static function bestMove(Board $board, bool $color, int $depth = 1, int $timePerMove = 5, bool $verbose = false): ?Notation {
        $cache = new Cache();

        $cacheKey = 'bestMove_'.$board->md5Board().'_'.($color ? 'w' : 'b');

        if ($cache->isset($cacheKey)) {
            return Notation::generateFromString($cache->get($cacheKey));
        }

        $pieces = [];

        $rules = new Rules();

        foreach ($board->jsonSerialize() as $letter => $col) {
            foreach ($col as $number => $piece) {
                if ($piece === null) {
                    continue;
                }
                if ($piece['color'] === $color) {
                    $pieces[] = [$letter, $number];
                }
            }
        }

        $bestMove = -5000;
        $bestMoveNotation = null;

        $start = microtime(true);

        foreach ($pieces as $piece) {
            [$letter, $number] = $piece;
            $position = new Position($letter, $number);

            $nowTime = microtime(true)-$start;

            if ($nowTime > ($timePerMove/$depth)) {
                break;
            }

            $allMoves = $rules->getAllMovesForPiece($board, $position);

            foreach ($allMoves as $move) {
                assert($move instanceof Notation);

                $fakeBoard = $board->makeBoardOfChanges(false, $move);

                $current = self::evaluateForColor($fakeBoard, $color);


                // Add depth

                if ($depth > 1) {
                    $bestDepthMove = self::bestMove($fakeBoard, !$color, $depth - 1, $timePerMove, $verbose);
                    $current += self::evaluateForColor($fakeBoard->makeBoardOfChanges(false, $bestDepthMove), $color);
                }

                if ($current > $bestMove) {
                    $bestMove = $current;
                    $bestMoveNotation = $move;
                    if ($verbose) {
                        print ($color ? "[WHITE]" : "[BLACK] ")."[$depth] New best move: " . $bestMoveNotation . " with value " . $bestMove . PHP_EOL;
                    }
                }
            }
        }

        $cache->set($cacheKey, (string) $bestMoveNotation);

        return $bestMoveNotation ?? null;
    }

    /**
     * @param Board $board
     * @param bool $color
     * @return float
     */
    private static function evaluateForColor(Board $board, bool $color = true): float
    {
        return $color
            ? self::evaluateBoard($board)
            : -self::evaluateBoard($board);
    }

    /**
     * @param Board $board
     * @return float
     */
    public static function evaluateBoard(Board $board): float
    {
        $eval = 0;

        $rules = new Rules();

        $becausePieces = 0;
        $becausePiecesBlack = 0;
        $becauseMoves = 0;
        $becauseMovesBlack = 0;
        $becauseTakes = 0;
        $becauseTakesBlack = 0;

        foreach ($board->getPieces(true) as $piece) {
            assert($piece instanceof Piece);
            $eval += self::VALUE[$piece->getName()] ?? 0;
            $becausePieces += self::VALUE[$piece->getName()] ?? 0;
        }

        foreach ($board->getPieces(false) as $piece) {
            assert($piece instanceof Piece);
            $eval -= self::VALUE[$piece->getName()] ?? 0;
            $becausePiecesBlack += self::VALUE[$piece->getName()] ?? 0;
        }

        foreach ($board->jsonSerialize() as $letter => $cols) {
            foreach ($cols as $number => $piece) {
                if ($piece === null) {
                    continue;
                }
                $pos = new Position($letter, $number);
                $moves = $rules->getAllMovesForPiece($board, $pos);
                $takes = $rules->getAllTakesForPiece($board, $pos);
                $factor = match($piece['piece']) {
                    'P' => 0.1,
                    'N' => 0.32,
                    'B' => 0.33,
                    'R' => 0.5,
                    'Q' => 0.9,
                    default => 0,
                };
                $change = count($moves) * $factor * 2;
                $change += count($takes) * $factor;
                if ($piece['color']) {
                    $eval += $change;
                    $becauseMoves += count($moves) * $factor * 2;
                    $becauseTakes += count($takes) * $factor;
                } else {
                    $eval -= $change;
                    $becauseMovesBlack += count($moves) * $factor * 2;
                    $becauseTakesBlack += count($takes) * $factor;
                }
            }
        }

        /*
        print "Changes due to:<br>";
        print "Pieces: White <b>".$becausePieces."</b> Black <b>".$becausePiecesBlack."</b><br>";
        print "Moves: White <b>".$becauseMoves."</b> Black <b>".$becauseMovesBlack."</b><br>";
        print "Takes: White <b>".$becauseTakes."</b> Black <b>".$becauseTakesBlack."</b><br>";
        */

        return $eval;
    }
}
