<?php
namespace RealChess\tests;

use PHPUnit\Framework\TestCase;
use RealChess\Board;
use RealChess\Notation;
use RealChess\Piece;
use RealChess\Position;

class CheckTest extends TestCase{

    private Board $board;

    public function setUp(): void {
        parent::setUp();

        $this->board = new Board();

        $this->board->initializeDefaultBoard();

        $this->board->movePiece(Notation::generateFromString('E2E4'));
        $this->board->movePiece(Notation::generateFromString('F7F5'));
    }

    public function testChecks(): void {
        self::assertEmpty($this->board->anyChecks());

        $this->board->movePiece(Notation::generateFromString('D1H5'));

        self::assertCount(2, $this->board->anyChecks());
    }
}
