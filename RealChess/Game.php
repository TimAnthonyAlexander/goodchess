<?php
namespace RealChess;


class Game {
    public static function start(bool $interactive = false): void{
        ini_set('max_execution_time', '4');

        // If session is not set, start session
        if(!isset($_SESSION)){
            session_start();
        }

        $cache = new Cache();
        $cache->load();

        $board = new Board();
        $board->initializeDefaultBoard();

        if ($interactive) {
            self::play($board);
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
                $board->viewTerminal();
            }
        }
    }

    private static function play(Board $board): void {
        print "\033[2J\033[;H";
        print "Chess by Tim Anthony Alexander - 2022".PHP_EOL.PHP_EOL;

        $rules = new Rules();
        $board->viewTerminal();

        $color = true;

        while(true){
            print "A move can be like this: E2-E4, e2e4, o-o-o".PHP_EOL;
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
                $board->anyChecks();
                $after = round(microtime(true) - $before, 2);
                $board->viewTerminal();
                print "Move took: " . $after . " seconds".PHP_EOL;
                if (isset($GLOBALS['checked']) && $GLOBALS['checked']) {
                    print "CHECKED!".PHP_EOL;
                }
                $before = microtime(true);
                $eval = round(TimFish::evaluateWhiteVsBlack($board), 3);
                $after = round(microtime(true) - $before, 2);
                print "Evaluation: " . $eval . " (" . $after . "s)".PHP_EOL;
            }
        }
    }
}
