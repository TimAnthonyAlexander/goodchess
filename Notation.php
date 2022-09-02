<?php
namespace RealChess;


class Notation{
    public function __construct(
        private readonly Position $from,
        private readonly Position $to
    ){}

    public function getFrom(): Position{
        return $this->from;
    }

    public function getTo(): Position{
        return $this->to;
    }

    public function __toString(): string{
        return $this->from . '-' . $this->to;
    }

    public static function generateFromString(string $notation): Notation{
        $notation = strtoupper($notation);
        $regex = '/([A-Z])([1-8])-([A-Z])([1-8])/';
        if (!preg_match($regex, $notation, $matches)) {
            return new Notation(new Position('a', 1), new Position('a', 1));
        }
        $notationArray = explode("-", $notation);
        return new Notation(Position::generateFromString($notationArray[0]), Position::generateFromString($notationArray[1]));
    }

    public function isHit(Board $board) {
        return $board[$this->getTo()->getLetter()][$this->getTo()->getNumber()] !== null;
    }

    public function isCheckMove(Board $board, bool $checkValidMove = false): bool {
        $from = $this->getFrom();
        $to = $this->getTo();
        $piece = $board->getPieceFromPosition($to);
        $attackingPiece = $board->getPieceFromPosition($from);

        assert($attackingPiece !== null);

        if ($piece === null) {
            return false;
        }

        $attackingColor = $attackingPiece->getColor();
        $rules = new Rules();
        if ($piece->getName() === "K") {
            if ($piece->getColor() !== $attackingColor) {
                if ($checkValidMove) {
                    if (!$rules->isValidFor($this, $board)) {
                        return false;
                    }
                }
                return true;
            }
        }
        return false;
    }
}
