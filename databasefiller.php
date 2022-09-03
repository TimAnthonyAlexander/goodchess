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
        $bestMove = TimFish::bestMove($board, $color, 2, 15);
        print "Moving [".($j+1)."]: " . $bestMove . PHP_EOL;
        $board->movePiece($bestMove);
        $color = !$color;
        sleep(4);
    }
    sleep(10);
    $cache->save();
    print "Game over." . PHP_EOL;
}
