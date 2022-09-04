<?php
namespace RealChess;


use Exception;

require("vendor/autoload.php");

ini_set('memory_limit', '1G');


$board = new Board();
$cache = new Cache();
$cache->load();

for ($i = 0; $i < 100; $i++) {
    print "Starting game ".($i+1).PHP_EOL;
    $board->initializeDefaultBoard();
    $color = true;
    for ($j = 0; $j < 50; $j++) {
        $playRandomMove = random_int(0, 3) === 1;
        if ($playRandomMove){
            playRandomMove($board, $color);

        }else{
            $_SESSION['moveCount'] = 0;
            $bestMove = TimFish::bestMove($board, $color, 2, 100, true);
            print "Calculated [" . $_SESSION['moveCount'] . "] moves - Playing best one." . PHP_EOL;
        }
        print "Moving [".($i+1)."][".($j+1)."]: " . $bestMove . PHP_EOL;
        $board->movePiece($bestMove);
        $board->viewTerminal();
        $color = !$color;
        sleep(4);
    }
    sleep(10);
    $cache->save();
    print "Game over." . PHP_EOL;
}

/**
 * @param Board $board
 * @param bool $color
 * @return Notation
 * @throws Exception
 */
function playRandomMove(Board $board, bool $color): Notation {
    $boardArray = $board->jsonSerialize();
    $pieces = [];
    $allMovesForPieces = [];
    foreach($boardArray as $letter => $col){
        foreach($col as $number => $piece){
            if($piece === null){
                continue;
            }
            if($piece['color'] === $color){
                $allMovesForPieces[$piece['uuid']] = $allMovesForPiece = (new Rules())->getAllMovesForPiece($board, new Position($letter, $number));
                if(count($allMovesForPiece) > 0){
                    $pieces[] = [$letter, $number, $piece['uuid']];
                }
            }
        }
    }

    $randomPiece = $pieces[random_int(0, count($pieces) - 1)];
    $allMoves = $allMovesForPieces[$randomPiece[2]];

    $bestMove = $allMoves[random_int(0, count($allMoves) - 1)];

    print "Playing random move." . PHP_EOL;

    return $bestMove;
}
