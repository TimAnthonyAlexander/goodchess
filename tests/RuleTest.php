<?php
namespace RealChess\tests;


use PHPUnit\Framework\TestCase;
use RealChess\Board;
use RealChess\Notation;
use RealChess\Rules;

class RuleTest extends TestCase{
    private Board $board;
    private Rules $rules;

    public function setUp(): void {
        $this->rules = new Rules();
        $this->board = new Board();
    }

    public function testPawn(): void {
        $this->board->initializeDefaultBoard();

        $validMoveOrder = [
            'e2e4',
            'e7e5',
            'd2d4',
            'd7d5',
            'd4e5',
        ];

        $invalidMoveOrder = [
            'e2e5',
            'e7e4',
            'h2h7',
        ];

        foreach ($validMoveOrder as $validMove) {
            self::assertTrue($this->doMove(Notation::generateFromString($validMove), $this->board), $validMove);
        }

        $this->board->initializeDefaultBoard();

        foreach ($invalidMoveOrder as $invalidMove) {
            self::assertFalse($this->doMove(Notation::generateFromString($invalidMove), $this->board));
        }
    }

    private function doMove(Notation $notation, Board $board): bool {
        if ($this->rules->isValidFor($notation, $board)) {
            $board->movePiece($notation);

            return true;
        }

        return false;
    }
}
