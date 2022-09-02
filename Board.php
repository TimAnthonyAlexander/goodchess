<?php
namespace RealChess;


use Ramsey\Uuid\Uuid;

class Board {
    private array $board;

    public function __construct(){
        for ($letterVal = 0; $letterVal<8; $letterVal++) {
            for ($numberVal = 0; $numberVal<8; $numberVal++) {
                $letter = chr(ord('a') + $letterVal);
                $number = $numberVal + 1;
                $this->board[$letter][$number] = null;
            }
        }
    }

    /**
     * @param Position $position
     * @param bool $step
     * @param int $right
     * @param int $up
     * @param Board $board
     * @return array
     */
    public static function walk(Position $position, bool $step, int $right, int $up, Board $board): array{
        $originalLetter = $letter = $position->getLetter();
        $originalNumber = $number = $position->getNumber();
        $result = [];
        if($step){
            $newLetter = self::calculateLetter($letter, $right);
            $newNumber = $number + $up;

            if (!in_array(strtolower($newLetter), ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'])) {
                return [];
            }
            if (!in_array($newNumber, range(1, 8), true)) {
                return [];
            }

            $result[] = new Notation(new Position($letter, $number), new Position($newLetter, $newNumber));
            return $result;
        }
        while($board->getPieceFromPosition(new Position($letter, $number)) !== null){
            $newLetter = self::calculateLetter($letter, $right);
            $newNumber = $number + $up;

            if (!in_array(strtolower($newLetter), ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'])) {
                continue;
            }
            if (!in_array($newNumber, range(1, 8), true)) {
                continue;
            }

            $result[] = new Notation(new Position($letter, $number), new Position($newLetter, $newNumber));
            $letter = $newLetter;
            $number = $newNumber;
        }
        $result[] = new Notation(new Position($originalLetter, $originalNumber), new Position($letter, $number));
        return $result;
    }

    public function initializeDefaultBoard(): void {
        // White
        $this->board['a'][1] = new Piece(['piece' => 'R', 'color' => true, 'uuid' => Uuid::uuid4()->toString()]);
        $this->board['b'][1] = new Piece(['piece' => 'N', 'color' => true, 'uuid' => Uuid::uuid4()->toString()]);
        $this->board['c'][1] = new Piece(['piece' => 'B', 'color' => true, 'uuid' => Uuid::uuid4()->toString()]);
        $this->board['d'][1] = new Piece(['piece' => 'Q', 'color' => true, 'uuid' => Uuid::uuid4()->toString()]);
        $this->board['e'][1] = new Piece(['piece' => 'K', 'color' => true, 'uuid' => Uuid::uuid4()->toString()]);
        $this->board['f'][1] = new Piece(['piece' => 'B', 'color' => true, 'uuid' => Uuid::uuid4()->toString()]);
        $this->board['g'][1] = new Piece(['piece' => 'N', 'color' => true, 'uuid' => Uuid::uuid4()->toString()]);
        $this->board['h'][1] = new Piece(['piece' => 'R', 'color' => true, 'uuid' => Uuid::uuid4()->toString()]);

        // Black
        $this->board['a'][8] = new Piece(['piece' => 'R', 'color' => false, 'uuid' => Uuid::uuid4()->toString()]);
        $this->board['b'][8] = new Piece(['piece' => 'N', 'color' => false, 'uuid' => Uuid::uuid4()->toString()]);
        $this->board['c'][8] = new Piece(['piece' => 'B', 'color' => false, 'uuid' => Uuid::uuid4()->toString()]);
        $this->board['d'][8] = new Piece(['piece' => 'Q', 'color' => false, 'uuid' => Uuid::uuid4()->toString()]);
        $this->board['e'][8] = new Piece(['piece' => 'K', 'color' => false, 'uuid' => Uuid::uuid4()->toString()]);
        $this->board['f'][8] = new Piece(['piece' => 'B', 'color' => false, 'uuid' => Uuid::uuid4()->toString()]);
        $this->board['g'][8] = new Piece(['piece' => 'N', 'color' => false, 'uuid' => Uuid::uuid4()->toString()]);
        $this->board['h'][8] = new Piece(['piece' => 'R', 'color' => false, 'uuid' => Uuid::uuid4()->toString()]);

        // Pawns
        for ($letterVal = 0; $letterVal<8; $letterVal++) {
            $letter = chr(ord('a') + $letterVal);
            $this->board[$letter][2] = new Piece(['piece' => 'P', 'color' => true, 'uuid' => Uuid::uuid4()->toString()]);
            $this->board[$letter][7] = new Piece(['piece' => 'P', 'color' => false, 'uuid' => Uuid::uuid4()->toString()]);
        }

        // Foreach piece run jsonSerialize on the object
        foreach ($this->board as $letter => $row) {
            foreach ($row as $number => $piece) {
                if ($piece !== null) {
                    $this->board[$letter][$number] = $piece->jsonSerialize();
                }
            }
        }
    }

    public function viewTerminal(): void {
        echo "  [aC][bC][cC][dC][eC][fC][gC][hC]".PHP_EOL;
        echo "----------------------------------------------------".PHP_EOL;
        for ($numberVal = 8; $numberVal>0; $numberVal--) {
            echo $numberVal." ";
            for ($letterVal = 0; $letterVal<8; $letterVal++) {
                $letter = chr(ord('a') + $letterVal);
                if ($this->board[$letter][$numberVal] == null) {
                    echo "[  ]";
                } else {
                    $piece = $this->getPieceFromPosition(new Position($letter, $numberVal));
                    echo "[".$piece->getName().($piece->getColor() ? "w" : "b")."]";
                }
            }
            echo PHP_EOL;
        }
        echo "----------------------------------------------------".PHP_EOL;
        echo "  [aC][bC][cC][dC][eC][fC][gC][hC]".PHP_EOL;
        echo PHP_EOL.PHP_EOL;
    }

    public function setPiece(Piece $piece, Position $pos): void {
        $this->board[$pos->getLetter()][$pos->getNumber()] = [
            "uuid" => $piece->getUuid(),
            "piece" => $piece->getName(),
            "color" => $piece->getColor(),
        ];
    }

    public function movePiece(Notation $notation): void {
        $piece = $this->getPieceFromPosition($notation->getFrom());

        if ($piece === null) {
            throw new \Exception("No piece found at ".$notation->getFrom()->getLetter().$notation->getFrom()->getNumber());
        }

        $this->clearPiece($notation->getFrom());

        $this->setPiece($piece, $notation->getTo());
    }

    public function clearPiece(Position $pos): void {
        $this->board[$pos->getLetter()][$pos->getNumber()] = null;
    }

    public static function calculateDiagonal(Position $position, Board $board, bool $step = false) {
        $resultOne = self::walkDiagonal($position, $board, $step, 1, 1);
        $resultTwo = self::walkDiagonal($position, $board, $step, 1, -1);
        $resultThree = self::walkDiagonal($position, $board, $step, -1, 1);
        $resultFour = self::walkDiagonal($position, $board, $step, -1, -1);

        return array_merge($resultOne, $resultTwo, $resultThree, $resultFour);
    }

    public static function walkDiagonal(Position $position, Board $board, bool $step, $right = 1, $up = 1): array {
        return self::walk($position, $step, $right, $up, $board);
    }

    public static function calculateLetter(string $letter = 'a', int $right = 1): string {
        $letter = strtolower($letter);

        return chr(ord($letter) + $right);
    }

    public function getPieceFromPosition(Position $pos): ?Piece {
        if (!isset($this->board[$pos->getLetter()][$pos->getNumber()])) {
            return null;
        }

        if ($this->board[$pos->getLetter()][$pos->getNumber()] === null) {
            return null;
        }
        return new Piece($this->board[$pos->getLetter()][$pos->getNumber()]);
    }

    public function makeBoardOfChanges(bool $checkChanges = false, Notation ...$change): Board {
        $board = clone $this;
        $rules = new Rules();
        foreach ($change as $move) {
            if ($checkChanges) {
                $rules->isValidFor($move, $board);
            }
            $board->movePiece($move);
        }
        return $board;
    }

    public function anyChecks(): array {
        $kings = [];

        foreach ($this->board as $letter => $row) {
            foreach ($row as $number => $piece) {
                if (($piece !== null) && $piece['piece'] === 'K'){
                    $kings[] = new Position($letter, $number);
                }
            }
        }

        $checks = [];

        foreach ($kings as $king) {
            $checks = array_merge($checks, $this->calculateChecksForKing($king));
        }

        return $checks;
    }

    public static function walkDiagonalBetween(Position $start, Position $end, Board $board): array {
        $result = [];
        $up = $start->getNumber() < $end->getNumber() ? 1 : -1;
        $right = $start->getLetter() < $end->getLetter() ? 1 : -1;
        $letter = $start->getLetter();
        $number = $start->getNumber();
        while ($letter !== $end->getLetter() || $number !== $end->getNumber()) {
            $letter = self::calculateLetter($letter, $right);
            $number += $up;
            if ($board->getPieceFromPosition(new Position($letter, $number)) !== null) {
                $result[] = new Position($letter, $number);
            }
        }
        return $result;
    }

    public static function calculateStraights(Position $position, Board $board, bool $step = false): array {
        $resultOne = self::calculateStraight($position, $board, $step, true);
        $resultTwo = self::calculateStraight($position, $board, $step, false);

        return array_merge($resultOne, $resultTwo);
    }

    public static function calculateStraight(Position $position, Board $board, bool $step, bool $horizontal): array {
        // If horizontal check left and right to the $position->getLetter()
        // Else check up and down
        if ($horizontal) {
            $result = self::walkStraight($position, $board, $step, -1);
            $result = array_merge($result, self::walkStraight($position, $board, $step, 1, 0));
        } else {
            $result = self::walkStraight($position, $board, $step, 0, -1);
            $result = array_merge($result, self::walkStraight($position, $board, $step, 0, 1));
        }

        return $result;
    }

    public static function calculateStraightBetween(Position $positionOne, Position $positionTwo, Board $board): array {
        if ($positionOne->getLetter() === $positionTwo->getLetter()) {
            $numberDirection = $positionOne->getNumber() < $positionTwo->getNumber() ? 1 : -1;
            $result = self::walkStraightBetween($positionOne, $positionTwo, $board, 0, $numberDirection);
        } else if ($positionOne->getNumber() === $positionTwo->getNumber()) {
            $letterDirection = ord($positionOne->getLetter()) < ord($positionTwo->getLetter()) ? 1 : -1;
            $result = self::walkStraightBetween($positionOne, $positionTwo, $board, 1, $letterDirection);
        } else {
            $result = [];
        }

        return $result;
    }

    public static function walkStraightBetween(Position $start, Position $end, Board $board, int $direction, int $extraDirection): array {
        $result = [];
        $letter = $start->getLetter();
        $number = $start->getNumber();

        while ($letter !== $end->getLetter() || $number !== $end->getNumber()) {
            if ($direction === 0) {
                $number += $extraDirection;
            } else {
                $letter = self::calculateLetter($letter, $extraDirection);
            }

            $result[] = new Position($letter, $number);

            if ($board->getPieceFromPosition(new Position($letter, $number)) !== null) {
                break;
            }
        }

        return $result;
    }

    public static function calculateKnights(Position $position, Board $board): array {
        $result = [];
        $result = array_merge($result, self::walkKnight($position, $board, 1, 2));
        $result = array_merge($result, self::walkKnight($position, $board, 2, 1));
        $result = array_merge($result, self::walkKnight($position, $board, -1, 2));
        $result = array_merge($result, self::walkKnight($position, $board, -2, 1));
        $result = array_merge($result, self::walkKnight($position, $board, 1, -2));
        $result = array_merge($result, self::walkKnight($position, $board, 2, -1));
        $result = array_merge($result, self::walkKnight($position, $board, -1, -2));
        return array_merge($result, self::walkKnight($position, $board, -2, -1));
    }

    public static function walkKnight(Position $position, Board $board, int $right, int $up): array {
        return self::walk($position, false, $right, $up, $board);
    }

    public static function walkStraight(Position $position, Board $board, bool $step = false, int $right = 0, int $up = 0): array {
        return self::walk($position, $step, $right, $up, $board);
    }

    public function calculateChecksForKing(Position $king): array {
        $kingPiece = $this->getPieceFromPosition($king);
        assert($kingPiece !== null);

        $diagonals = self::calculateDiagonal($king, $this);
        $diagonalShorts = self::calculateDiagonal($king, $this, true);
        $straights = self::calculateStraights($king, $this);
        $knights = self::calculateKnights($king, $this);

        $checks = [];

        foreach ($diagonals as $diagonal) {
            assert ($diagonal instanceof Notation);
            $piece = $this->getPieceFromPosition($diagonal->getTo());
            if ($piece === null) {
                continue;
            }
            if ($piece->getColor() !== $kingPiece->getColor()) {
                if (in_array($piece->getName(), ["B", "Q"])) {
                    $checks[] = [$diagonal->getTo(), $king];
                }
            }
        }

        foreach ($diagonalShorts as $diagonal) {
            assert ($diagonal instanceof Notation);
            $piece = $this->getPieceFromPosition($diagonal->getTo());
            if ($piece === null) {
                continue;
            }
            if ($piece->getColor() !== $kingPiece->getColor()) {
                if ($piece->getName() === "P") {
                    $rules = new Rules();
                    if ($rules->isValidFor(new Notation($diagonal->getTo(), $king), $this)) {
                        $checks[] = [$diagonal->getTo(), $king];
                    }
                }
            }
        }

        foreach ($straights as $straight) {
            assert ($straight instanceof Notation);
            $piece = $this->getPieceFromPosition($straight->getTo());
            if ($piece === null) {
                continue;
            }
            if ($piece->getColor() !== $kingPiece->getColor()) {
                if (in_array($piece->getName(), ["R", "Q"])) {
                    $checks[] = [$straight->getTo(), $king];
                }
            }
        }

        return $checks;
    }
}
