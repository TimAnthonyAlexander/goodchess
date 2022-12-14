<?php
namespace RealChess;


use Exception;
use Ramsey\Uuid\Uuid;

class Board {
    private array $board;

    private Notation $lastMove;

    private bool $lastColor = false;

    /**
     * @return Notation
     */
    public function getLastMove(): Notation{
        return $this->lastMove;
    }

    /**
     * @param Notation $lastMove
     */
    public function setLastMove(Notation $lastMove): void{
        $this->lastMove = $lastMove;
    }

    /**
     *
     */
    public function __construct(){
        for ($letterVal = 0; $letterVal<8; $letterVal++) {
            for ($numberVal = 0; $numberVal<8; $numberVal++) {
                $letter = chr(ord('a') + $letterVal);
                $number = $numberVal + 1;
                $this->board[$letter][$number] = null;
            }
        }
        $this->lastMove = new Notation(Position::generateFromString('E2'), Position::generateFromString('E2'));
    }

    /**
     * @param Position $position
     * @param bool $step
     * @param int $right
     * @param int $up
     * @param Board $board
     * @return array
     */
    public static function calculate(Position $position, bool $step, int $right, int $up, Board $board): array{
        $originalLetter = $letter = $position->getLetter();
        $originalNumber = $number = $position->getNumber();
        $result = [];

        $result[] = new Notation(new Position($letter, $number), new Position($letter, $number));

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
        for ($i = 0; $i < 8; $i++){
            $newLetter = self::calculateLetter($letter, $right);
            $newNumber = $number + $up;

            if (!in_array(strtolower($newLetter), ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'])) {
                break;
            }
            if (!in_array($newNumber, range(1, 8), true)) {
                break;
            }

            $result[] = new Notation(new Position($letter, $number), new Position($newLetter, $newNumber));


            $letter = $newLetter;
            $number = $newNumber;

            if ($board->getPieceFromPosition(new Position($letter, $number)) !== null) {
                break;
            }
        }
        $result[] = new Notation(new Position($originalLetter, $originalNumber), new Position($letter, $number));
        return $result;
    }

    /**
     * @return void
     */
    public function initializeDefaultBoard(): void {
        // Clear
        foreach ($this->board as $letter => $numbers) {
            foreach ($numbers as $number => $piece) {
                $this->board[$letter][$number] = null;
            }
        }
        $this->lastColor = false;
        $this->lastMove = new Notation(Position::generateFromString('E2'), Position::generateFromString('E2'));

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

    /**
     * @return void
     */
    public function viewTerminal(): void {
        echo "   a   b   c   d   e   f   g   h".PHP_EOL;
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
        echo "   a   b   c   d   e   f   g   h".PHP_EOL;
        echo PHP_EOL.PHP_EOL;
    }

    /**
     * @param Piece $piece
     * @param Position $pos
     * @return void
     */
    public function setPiece(Piece $piece, Position $pos): void {
        $this->board[$pos->getLetter()][$pos->getNumber()] = [
            "uuid" => $piece->getUuid(),
            "piece" => $piece->getName(),
            "color" => $piece->getColor(),
        ];
    }

    /**
     * @param Notation $notation
     * @return void
     * @throws Exception
     */
    public function movePiece(Notation $notation): void {
        $piece = $this->getPieceFromPosition($notation->getFrom());

        if ($piece === null) {
            throw new Exception("No piece found at ".$notation->getFrom()->getLetter().$notation->getFrom()->getNumber());
        }

        $this->clearPiece($notation->getFrom());

        $this->setPiece($piece, $notation->getTo());

        $this->setLastMove($notation);
        $this->setLastColor($piece->getColor());
    }

    /**
     * @return bool
     */
    public function getLastColor(): bool{
        return $this->lastColor;
    }

    /**
     * @param bool $lastColor
     */
    public function setLastColor(bool $lastColor): void{
        $this->lastColor = $lastColor;
    }

    /**
     * @param Position $pos
     * @return void
     */
    public function clearPiece(Position $pos): void {
        $this->board[$pos->getLetter()][$pos->getNumber()] = null;
    }

    /**
     * @param Position $position
     * @param Board $board
     * @param bool $step
     * @return array
     */
    public static function calculateDiagonals(Position $position, Board $board, bool $step = false) {
        $resultOne = self::calculateDiagonal($position, $board, $step);
        $resultTwo = self::calculateDiagonal($position, $board, $step, up: -1);
        $resultThree = self::calculateDiagonal($position, $board, $step, right: -1);
        $resultFour = self::calculateDiagonal($position, $board, $step, right: -1, up: -1);

        return array_merge($resultOne, $resultTwo, $resultThree, $resultFour);
    }

    /**
     * @param Position $position
     * @param Board $board
     * @param bool $step
     * @param $right
     * @param $up
     * @return array
     */
    public static function calculateDiagonal(Position $position, Board $board, bool $step, $right = 1, $up = 1): array {
        return self::calculate($position, $step, $right, $up, $board);
    }

    /**
     * @param string $letter
     * @param int $right
     * @return string
     */
    public static function calculateLetter(string $letter = 'a', int $right = 1): string {
        $letter = strtolower($letter);

        return chr(ord($letter) + $right);
    }

    /**
     * @param bool $color
     * @param bool $asPosition
     * @return array<int, Piece>
     */
    public function getPieces(?bool $color = null, bool $asPosition = false): array {
        $pieces = [];
        foreach ($this->board as $letter => $row) {
            foreach ($row as $number => $piece) {
                if ($piece === null) {
                    continue;
                }
                if ($color === null) {
                    $pieces[] = $asPosition ? new Position($letter, $number) : new Piece($piece);
                } else if ($piece['color'] === $color) {
                    $pieces[] = $asPosition ? new Position($letter, $number) : new Piece($piece);
                }
            }
        }

        return $pieces;
    }

    /**
     * @param Position $pos
     * @return Piece|null
     */
    public function getPieceFromPosition(Position $pos): ?Piece {
        if (!isset($this->board[$pos->getLetter()][$pos->getNumber()])) {
            return null;
        }

        if ($this->board[$pos->getLetter()][$pos->getNumber()] === null) {
            return null;
        }
        return new Piece($this->board[$pos->getLetter()][$pos->getNumber()]);
    }

    /**
     * @param bool $checkChanges
     * @param Notation ...$change
     * @return Board
     * @throws Exception
     */
    public function makeBoardOfChanges(bool $checkChanges = false, Notation ...$change): Board {
        $board = clone $this;
        $rules = new Rules();
        foreach ($change as $move) {
            if ($checkChanges) {
                $rules->isValidFor($move, $board, isFake: $checkChanges);
            }
            $board->movePiece($move);
        }
        return $board;
    }

    /**
     * @return array
     * @throws Exception
     */
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

    /**
     * @param Position $start
     * @param Position $end
     * @param Board $board
     * @return array
     */
    public static function walkDiagonalBetween(Position $start, Position $end, Board $board): array {
        $result = [];
        $up = $start->getNumber() < $end->getNumber() ? 1 : -1;
        $right = $start->getLetter() < $end->getLetter() ? 1 : -1;
        $letter = $start->getLetter();
        $number = $start->getNumber();
        $count = 0;
        while ($letter !== $end->getLetter() || $number !== $end->getNumber()) {
            if ($count === 8) {
                break;
            }
            $letter = self::calculateLetter($letter, $right);
            $number += $up;
            $result[] = new Position($letter, $number);
            if ($board->getPieceFromPosition(new Position($letter, $number)) !== null) {
                break;
            }
            $count++;
        }
        return $result;
    }

    /**
     * @param Position $position
     * @param Board $board
     * @param bool $step
     * @return array
     */
    public static function calculateStraights(Position $position, Board $board, bool $step = false): array {
        $resultOne = self::calculateStraight($position, $board, $step, true);
        $resultTwo = self::calculateStraight($position, $board, $step, false);

        return array_merge($resultOne, $resultTwo);
    }

    /**
     * @param Position $position
     * @param Board $board
     * @param bool $step
     * @param bool $horizontal
     * @return array
     */
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

    /**
     * @param Position $positionOne
     * @param Position $positionTwo
     * @param Board $board
     * @return array
     */
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

    /**
     * @param Position $start
     * @param Position $end
     * @param Board $board
     * @param int $direction
     * @param int $extraDirection
     * @return array
     */
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

    /**
     * @param Position $position
     * @param Board $board
     * @return array
     */
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

    /**
     * @param Position $position
     * @param Board $board
     * @param int $right
     * @param int $up
     * @return array
     */
    public static function walkKnight(Position $position, Board $board, int $right, int $up): array {
        return self::calculate($position, false, $right, $up, $board);
    }

    /**
     * @param Position $position
     * @param Board $board
     * @param bool $step
     * @param int $right
     * @param int $up
     * @return array
     */
    public static function walkStraight(Position $position, Board $board, bool $step = false, int $right = 0, int $up = 0): array {
        return self::calculate($position, $step, $right, $up, $board);
    }

    /**
     * @param Position $king
     * @return array
     * @throws Exception
     */
    public function calculateChecksForKing(Position $king): array {
        $kingPiece = $this->getPieceFromPosition($king);
        assert($kingPiece !== null);

        $diagonals = self::calculateDiagonals($king, $this);

        $diagonalShorts = self::calculateDiagonals($king, $this, true);

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

        foreach ($knights as $knight) {
            assert ($knight instanceof Notation);
            $piece = $this->getPieceFromPosition($knight->getTo());
            if ($piece === null) {
                continue;
            }
            if ($piece->getColor() !== $kingPiece->getColor()) {
                if ($piece->getName() === "N") {
                    $checks[] = [$knight->getTo(), $king];
                }
            }
        }

        return $checks;
    }

    /**
     * @throws \JsonException
     */
    public function md5Board(): string {
        return md5(json_encode($this->serializeBoard(), JSON_THROW_ON_ERROR));
    }

    public function serializeBoard(): array{
        $data = [];
        foreach ($this->board as $row){
            foreach ($row as $piece){
                if ($piece === null){
                    $data[] = '0';
                } else {
                    $data[] = $piece['piece'];
                }
            }
        }
        return $data;
    }

    public function jsonSerialize(): array {
        return $this->board;
    }
}
