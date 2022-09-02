<?php /** @noinspection ALL */

namespace RealChess;


class TimFish{
    public const WEIGHTS = ['P' => 100, 'N' => 320, 'B' => 330, 'R' => 500, 'Q' => 900, 'K' => 20000];

    public const RULEWEIGHTS = ['check' => 150, 'checkmate' => 100000, 'piececount' => 1, 'movecount' => 0.05,];

    public static function countMaterial(Board $board, bool $color): int{
        $material = 0;
        $pieces = $board->getPieces($color);
        foreach($pieces as $piece){
            assert($piece instanceof Piece);
            $material += self::WEIGHTS[$piece->getName()] * self::RULEWEIGHTS['piececount'];
        }

        return $material;
    }

    public static function countChecks(Board $board, bool $color = true): int{
        $count = 0;
        $counted = [];

        foreach($board->anyChecks() as $check){
            [$checkingPiecePos,] = $check;
            assert($checkingPiecePos instanceof Position);

            $checkingPiece = $board->getPieceFromPosition($checkingPiecePos);
            assert($checkingPiece instanceof Piece);

            if(!in_array($check, $counted, true) && $checkingPiece->getColor()){
                $counted[] = $check;
                $count++;
            }
        }

        return $count;
    }

    private static function getMovesCached(Board $board, bool $color): array {
        $md5 = $board->md5Board();
        $cacheName = 'moves_' . $md5 . '_' . ($color ? 'w' : 'b');

        if (isset($GLOBALS['moveCache'][$cacheName])) {
            return $GLOBALS['moveCache'][$cacheName];
        }

        $pieces = $board->getPieces($color, true);
        $rules = new Rules();
        $moves = [];

        foreach($pieces as $piece){
            assert($piece instanceof Position);
            $moves[] = $rules->getAllMovesForPiece($board, $piece);
        }

        $GLOBALS['moveCache'][$cacheName] = $moves;

        return $moves;
    }

    public static function countMoves(Board $board, bool $color = true): int{
        $getMoves = self::getMovesCached($board, $color);

        return count($getMoves);
    }

    public static function evaluate(Board $board, bool $color = true): float{
        $md5 = $board->md5Board();
        $cache = new Cache();

        if($cache->isset($md5 . '-color-' . ($color ? 'w' : 'b'))){
            print "Cache hit on: " . $md5 . '-color-' . ($color ? 'w' : 'b') . PHP_EOL;
            return $cache->get($md5 . '-color-' . ($color ? 'w' : 'b'));
        }

        $material = self::countMaterial($board, $color);
        $checks = self::countChecks($board, $color);
        $moves = self::countMoves($board, $color);

        $materialValue = $material * self::RULEWEIGHTS['piececount'];
        $checkValue = $checks * self::RULEWEIGHTS['check'];
        $moveValue = $moves * self::RULEWEIGHTS['movecount'];

        if(isset($argv[1]) && $argv[1] === 'debug'){
            print "Material: " . $material . " = " . $materialValue . PHP_EOL;
            print "Checks: " . $checks . " = " . $checkValue . PHP_EOL;
            print "Moves: " . $moves . " = " . $moveValue . PHP_EOL;
        }

        $return = $materialValue + $checkValue + $moveValue;

        print "Cache set on: " . $md5 . '-color-' . ($color ? 'w' : 'b') . PHP_EOL;
        $cache->set($md5 . '-color-' . ($color ? 'w' : 'b'), $return);
        $cache->save();

        return $return;
    }

    public static function evaluateWhiteVsBlack(Board $board, bool $forWhite = true): float{
        $white = self::evaluate($board, true);
        $black = self::evaluate($board, false);

        return $forWhite ? $white - $black : $black - $white;
    }

    public static function allPossibleMoves(Board $board, bool $color = true, int $depth = 1, int $i = 0): array {
        $moves = self::getMovesCached($board, $color);

        $return = [];

        if ($i === $depth) {
            return $moves;
        }

        foreach($moves as $piece){
            if (is_array($piece)){
                foreach($piece as $move){
                    assert($move instanceof Notation);
                    $fakeBoard = $board->makeBoardOfChanges(false, $move);

                    $moves = array_merge($moves, self::allPossibleMoves($fakeBoard, !$color, $depth, $i + 1));
                }
            }
        }


        return $return;
    }

}
