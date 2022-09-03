<?php
namespace RealChess;


require("vendor/autoload.php");


$board = new Board();
$cache = new Cache();
$cache->load();

for ($i = 0; $i < 100; $i++) {
    print "Starting game ".($i+1).PHP_EOL;
    $board->initializeDefaultBoard();
    $color = true;
    for ($j = 0; $j < 50; $j++) {
        $randomBool = random_int(0, 3) !== 1;
        if ($randomBool){
            $bestMove = TimFish::bestMove($board, $color, 2, 4);
            print "Playing best move.".PHP_EOL;
        } else {
            $boardArray = $board->jsonSerialize();
            $pieces = [];
            $allMovesForPieces = [];
            foreach ($boardArray as $letter => $col) {
                foreach ($col as $number => $piece) {
                    if ($piece === null) {
                        continue;
                    }
                    if ($piece['color'] === $color) {
                        $allMovesForPieces[$piece['uuid']] = $allMovesForPiece = (new Rules())->getAllMovesForPiece($board, new Position($letter, $number));
                        if (count($allMovesForPiece) > 0) {
                            $pieces[] = [$letter, $number, $piece['uuid']];
                        }
                    }
                }
            }

            $randomPiece = $pieces[random_int(0, count($pieces)-1)];
            $allMoves = $allMovesForPieces[$randomPiece[2]];

            $bestMove = $allMoves[random_int(0, count($allMoves)-1)];

            print "Playing random move.".PHP_EOL;
        }
        print "Moving [".($j+1)."]: " . $bestMove . PHP_EOL;
        $board->movePiece($bestMove);
        $board->viewTerminal();
        $color = !$color;
        sleep(4);
    }
    sleep(10);
    $cache->save();
    print "Game over." . PHP_EOL;
}
