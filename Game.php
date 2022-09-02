<?php
namespace RealChess;


class Game {
    public static function start(bool $interactive = false): void{
        // If session is not set, start session
        if(!isset($_SESSION)){
            session_start();
        }

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
        $rules = new Rules();
        $board->viewTerminal();

        $color = true;

        while(true){
            print "Enter Move (E2-E4): ";
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
                    break;
                }
                $board->movePiece($notation);
                $board->viewTerminal();
            }
        }
    }
}
