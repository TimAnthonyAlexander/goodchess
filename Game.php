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
            "E4-D5",
        ];

        $rules = new Rules();

        foreach ($moves as $move) {
            $notation = Notation::generateFromString($move);
            $valid = $rules->isValidFor($notation, $board);
            if (!$valid) {
                print "Move [".$notation."] invalid".PHP_EOL;
            } else {
                $board->movePiece($notation);
                $board->viewTerminal();
            }
        }
    }

    private static function play(Board $board): void {
        $rules = new Rules();
        $board->viewTerminal();

        while(true){
            print "Enter Move (E2-E4): ";
            $handle = fopen ("php://stdin","rb");
            $line = fgets($handle);
            $inputData = trim($line);
            $move = $inputData;
            $notation = Notation::generateFromString($move);
            $valid = $rules->isValidFor($notation, $board);
            if(!$valid){
                print "Move [" . $notation . "] invalid" . PHP_EOL;
            }else{
                $board->movePiece($notation);
                if ($board->anyChecks() !== []) {
                    print "There are checks:" . PHP_EOL;
                    foreach ($board->anyChecks() as $check) {
                        print $check . PHP_EOL;
                    }
                }
                $board->viewTerminal();
            }
        }
    }
}
