<?php
namespace RealChess;


use Exception;
use JsonException;

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

        $_SESSION['moveCount']++;

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

                $current = self::evaluateForColor($fakeBoard, $color, $verbose);

                // Add depth

                if ($depth > 1) {
                    $otherColor = !$color;
                    $bestDepthMove = self::bestMove($fakeBoard, $otherColor, $depth - 1, $timePerMove);
                    $current += self::evaluateForColor($fakeBoard->makeBoardOfChanges(false, $bestDepthMove), $color);
                }

                if ($current > $bestMove) {
                    $bestMove = $current;
                    $bestMoveNotation = $move;
                }
            }
        }

        $cache->set($cacheKey, (string) $bestMoveNotation);

        return $bestMoveNotation ?? null;
    }

    /**
     * @param Board $board
     * @param bool $color
     * @param bool $verbose
     * @return float
     * @throws JsonException
     */
    private static function evaluateForColor(Board $board, bool $color = true, bool $verbose = false): float
    {
        return $color
            ? self::evaluateBoard($board, $verbose)
            : -self::evaluateBoard($board, $verbose);
    }

    /**
     * @param Board $board
     * @param bool $verbose
     * @return float
     * @throws JsonException
     */
    public static function evaluateBoard(Board $board, bool $verbose = false): float
    {

        $cache = new Cache();
        if ($cache->isset('evaluateBoard_'.$board->md5Board())) {
            return $cache->get('evaluateBoard_'.$board->md5Board());
        }

        $eval = 0;

        $becausePieces = 0;
        $becausePiecesBlack = 0;

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

        $lastRowStuff = self::getLastRowPieces($board, true);
        $lastRowStuffBlack = self::getLastRowPieces($board, false);
        $lastRowPenaltyFactor = 2;
        $lastRowPenalty = count($lastRowStuff) * $lastRowPenaltyFactor;
        $lastRowPenaltyBlack = count($lastRowStuffBlack) * $lastRowPenaltyFactor;
        $eval -= $lastRowPenalty - $lastRowPenaltyBlack;


        $newline = PHP_SAPI === 'cli' ? PHP_EOL : '<br>';

        if ($verbose){
            print "Pieces: White " . $becausePieces . " - Black " . $becausePiecesBlack . " - " . round(($becausePieces-$becausePiecesBlack), 2) .$newline;
            print "Last row penalty: White " . $lastRowPenalty . " - Black " . $lastRowPenaltyBlack . " - " . round(($lastRowPenalty-$lastRowPenaltyBlack), 2) .$newline;
        }

        $cache->set('evaluateBoard_'.$board->md5Board(), $eval);

        return $eval;
    }

    public static function getLastRowPieces(Board $board, bool $color): array
    {
        $lastRow = $color ? 1 : 8;
        $pieces = [];
        foreach ($board->jsonSerialize() as $letter => $cols) {
            foreach ($cols as $number => $piece) {
                if ($piece === null || $number !== $lastRow) {
                    continue;
                }
                if ($piece['piece'] === 'K' || $piece['piece'] === 'R') {
                    continue;
                }
                if ($piece['color'] === $color){
                    $pieces[] = $piece['piece'];
                }
            }
        }
        return $pieces;
    }
}
