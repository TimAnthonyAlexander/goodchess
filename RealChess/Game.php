<?php
namespace RealChess;


class Game {
    public static function start(bool $interactive = false, bool $blind = false): void{
        ini_set('max_execution_time', '4');

        $cache = new Cache();
        $cache->load();

        $board = new Board();
        $board->initializeDefaultBoard();

        if ($interactive) {
            self::play($board, $blind, $cache);
            return;
        }

        $moves = [
            "E2-E4",
            "D7-D5",
            "F1-C4",
            "A7-A6",
            "B1-C3",
            "G8-H6",
        ];

        $rules = new Rules();

        $color = true;

        foreach ($moves as $move) {
            $notation = Notation::generateFromString($move);
            print ($board->getPieceFromPosition($notation->getFrom())->getColor() ? "White" : "Black") . " " . PHP_EOL;
            print ($color ? "White" : "Black") . " " . PHP_EOL;
            $valid = $rules->isValidFor($notation, $board);
            if (!$valid) {
                print "Move [".$notation."] invalid".PHP_EOL;
            } else {
                if ($board->getPieceFromPosition($notation->getFrom())->getColor() === $color) {
                    $color = !$color;
                } else {
                    print "Invalid move by COLOR: " . $notation . PHP_EOL;
                    break;
                }
                $board->movePiece($notation);
                if (!$blind){
                    $board->viewTerminal();
                } else {
                    print $notation;
                }
            }
        }
    }

    private static function play(Board $board, bool $blind, Cache $cache): void {
        print "\033[2J\033[;H";
        print "Chess by Tim Anthony Alexander - 2022".PHP_EOL.PHP_EOL;

        $rules = new Rules();
        if (!$blind) {
            $board->viewTerminal();
        }

        $color = true;

        print "A move can be like this: E2-E4, e2e4, o-o-o".PHP_EOL;

        while(true){
            print "Enter Move for ". ($color ? "white" : "black") .": ";
            $handle = fopen ("php://stdin","rb");
            $line = fgets($handle);
            $inputData = trim($line);
            if ($inputData === "exit") {
                break;
            }

            $notation = Notation::generateFromString($inputData);
            $valid = $rules->isValidFor($notation, $board);

            if (!$valid) {
                print "Move [".$notation."] invalid".PHP_EOL;
            } else {
                if ($board->getPieceFromPosition($notation->getFrom())->getColor() === $color) {
                    $color = !$color;
                } else {
                    print "Invalid move by COLOR: " . $notation . PHP_EOL;
                    $board->viewTerminal();
                    break;
                }
                print "\033[2J\033[;H";
                print "Chess by Tim Anthony Alexander - 2022".PHP_EOL.PHP_EOL;
                $before = microtime(true);
                $board->movePiece($notation);
                $board->movePiece(TimFish::bestMove($board, false, 2, 30, false));
                $color = !$color;
                $board->anyChecks();
                $after = round(microtime(true) - $before, 2);
                if (!$blind) {
                    $board->viewTerminal();
                } else {
                    print 'Your move: ' . $notation.PHP_EOL;
                    print 'Answer: ' . $board->getLastMove().PHP_EOL.PHP_EOL;
                }
                print "Move took: " . $after . " seconds".PHP_EOL;
                if (isset($GLOBALS['checked']) && $GLOBALS['checked']) {
                    print "CHECKED!".PHP_EOL;
                }
                $before = microtime(true);
                $eval = round(TimFish::evaluateBoard($board), 3);
                $after = round(microtime(true) - $before, 2);
                print "Evaluation: " . $eval . " (" . $after . "s)".PHP_EOL;
                $cache->save();
            }
        }
    }
}
