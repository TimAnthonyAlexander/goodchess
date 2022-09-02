<?php
namespace RealChess\tests;


use PHPUnit\Framework\TestCase;
use RealChess\Board;
use RealChess\Notation;
use RealChess\Position;

class BoardTest extends TestCase{
    private Board $board;

    public function setUp(): void {
        parent::setUp();

        $this->board = new Board();
    }

    private function testInit(): void {
        $this->board->initializeDefaultBoard();

        self::assertSame($this->board->getPieceFromPosition(Position::generateFromString('E2'))->getName(), 'P');
        self::assertSame($this->board->getPieceFromPosition(Position::generateFromString('H7'))->getName(), 'P');
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testMd5(): void {
        $this->testInit();

        $md5 = $this->board->md5Board();

        self::assertSame('a287108758638b4fa18a3bed1cddf5ff', $md5);
        $this->board->movePiece(new Notation(Position::generateFromString('E2'), Position::generateFromString('E4')));

        self::assertNotSame('a287108758638b4fa18a3bed1cddf5ff', $this->board->md5Board());
    }
}
