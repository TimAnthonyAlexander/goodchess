<?php
namespace RealChess;


use Generator;

class Rules{
    /*
     * All these rules in the constants have a function in the Rules class
     */

    public const KING_RULES = ["CASTLE", "DIAGONAL_STEP", "LINEAR_STEP", "DIAGONAL_STEP_TAKE", "LINEAR_STEP_TAKE", "NO_CHECK"];

    public const QUEEN_RULES = ["DIAGONAL_ALL", "LINEAR_ALL", "DIAGONAL_ALL_TAKE", "LINEAR_ALL_TAKE"];

    public const ROOK_RULES = ["LINEAR_ALL", "LINEAR_ALL_TAKE"];

    public const BISHOP_RULES = ["DIAGONAL_ALL", "DIAGONAL_ALL_TAKE"];

    public const KNIGHT_RULES = ["KNIGHT_STEP", "KNIGHT_STEP_TAKE"];

    public const PAWN_RULES = ["PAWN_STEP", "PAWN_DOUBLE_STEP", "DIAGONAL_STEP_TAKE", "PAWN_EN_PASSANT"];

    /**
     * @param Notation $notation
     * @param Board $board
     * @return bool
     */
    public function isValidFor(Notation $notation, Board $board, string $overridePiece = null): bool{
        $piece = $board->getPieceFromPosition($notation->getFrom());

        if($piece === null){
            return false;
        }

        if($notation->getFrom()->getLetter() === $notation->getTo()->getLetter() && $notation->getFrom()->getNumber() === $notation->getTo()->getNumber()){
            return false;
        }

        $name = $overridePiece ?? $piece->getName();

        $rules = match ($name) {
            "K" => self::KING_RULES,
            "Q" => self::QUEEN_RULES,
            "R" => self::ROOK_RULES,
            "B" => self::BISHOP_RULES,
            "N" => self::KNIGHT_RULES,
            "P" => self::PAWN_RULES,
        };

        foreach($rules as $rule){
            if($this->checkRule($notation, $board, $rule)){
                return true;
            }
        }

        return false;
    }

    /**
     * @param Notation $notation
     * @param Board $board
     * @param string $ruleName
     * @return bool
     * @throws \Exception
     */
    public function checkRule(Notation $notation, Board $board, string $ruleName): bool{
        if(!method_exists($this, $ruleName)){
            return false;
        }

        $anyChecks = $board->anyChecks();

        $fakeBoard = $board->makeBoardOfChanges(false, $notation);

        if ($fakeBoard->anyChecks() !== []) {
            foreach ($fakeBoard->anyChecks() as $check) {
                [, $checkedPiecePos] = $check;
                // If the checked Piece color is the same as the color of the piece that is moving, it is not a valid move
                $movingPiece = $board->getPieceFromPosition($notation->getFrom());

                assert($movingPiece !== null);

                if ($movingPiece->getColor() === $fakeBoard->getPieceFromPosition($checkedPiecePos)->getColor()) {
                    return false;
                }
            }
            print "Check!";
            $GLOBALS['checked'] = true;
        } else {
            $GLOBALS['checked'] = false;
        }

        if ($anyChecks !== []) {
            foreach ($anyChecks as $anyCheck) {
                [$checkingPiecePos, $checkedPiecePos] = $anyCheck;
                assert($checkingPiecePos instanceof Position);
                assert($checkedPiecePos instanceof Position);

                $checkingPiece = $board->getPieceFromPosition($checkingPiecePos);
                $checkedPiece = $board->getPieceFromPosition($checkedPiecePos);

                print "Check!".PHP_EOL;
                print "Checking piece: " . $checkingPiece . $checkingPiecePos . PHP_EOL;
                print "Checked piece: " . $checkedPiece . $checkedPiecePos . PHP_EOL;
            }
        }

        if (!$this->checkCheck($notation, $board, $anyChecks)) {
            return false;
        }

        $return = $this->$ruleName($notation, $board);

        if ($ruleName === 'CASTLE') {
            if ($return) {
                $straight = Board::calculateStraightBetween($notation->getFrom(), $notation->getTo(), $board);

                foreach ($straight as $position) {
                    // If king would be in check on any of the positions, castle is not allowed
                    if ($board->calculateChecksForKing($position) !== []) {
                        return false;
                    }
                }

                // Put the king on the $straight[1] position and the rook on the $straight[2] position
                $notationKing = new Notation($notation->getFrom(), $straight[1]);
                $notationRook = new Notation($notation->getTo(), $straight[2]);

                $board->movePiece($notationKing);
                $board->movePiece($notationRook);
            }
        }

        return $return;
    }

    /**
     * @param Notation $notation
     * @param Board $board
     * @return bool
     */
    private function PAWN_STEP(Notation $notation, Board $board): bool{
        $from = $notation->getFrom();
        $to = $notation->getTo();
        $piece = $board->getPieceFromPosition($from);

        assert($piece !== null);

        $attackingColor = $piece->getColor();

        if($board->getPieceFromPosition($to) !== null){
            return false;
        }

        // If the distance between the two positions is not 1, return false
        if(abs($from->getNumber() - $to->getNumber()) !== 1){
            return false;
        }

        if($from->getLetter() !== $to->getLetter()){
            return false;
        }

        return true;
    }

    /**
     * @param Notation $notation
     * @param Board $board
     * @return bool
     */
    private function PAWN_DOUBLE_STEP(Notation $notation, Board $board): bool{
        $from = $notation->getFrom();
        $to = $notation->getTo();
        $piece = $board->getPieceFromPosition($from);
        $toPiece = $board->getPieceFromPosition($to);

        assert($piece !== null);

        if($toPiece !== null){
            return false;
        }

        $attackingColor = $piece->getColor();

        if($from->getLetter() !== $to->getLetter()){
            return false;
        }

        if($from->getNumber() !== 2 && $from->getNumber() !== 7){
            return false;
        }

        // If distance traveled not 2, return false
        if(abs($from->getNumber() - $to->getNumber()) !== 2){
            return false;
        }

        if($board->getPieceFromPosition(new Position($from->getLetter(), $from->getNumber() + ($attackingColor ? 1 : -1))) !== null){
            return false;
        }

        return true;
    }

    /**
     * @param Notation $notation
     * @param Board $board
     * @return bool
     */
    private function DIAGONAL_STEP_TAKE(Notation $notation, Board $board): bool{
        $from = $notation->getFrom();
        $to = $notation->getTo();
        $piece = $board->getPieceFromPosition($from);
        $toPiece = $board->getPieceFromPosition($to);

        assert($piece !== null);

        if($toPiece === null){
            return false;
        }

        $attackingColor = $piece->getColor();
        $toColor = $toPiece->getColor();

        if($attackingColor === $toColor){
            return false;
        }

        // Check distance traveled for $letter to be 1
        if(Board::calculateLetter($from->getLetter(), 1) !== $to->getLetter() && Board::calculateLetter($from->getLetter(), -1) !== $to->getLetter()){
            return false;
        }

        // Check distance traveled for $number to be 1
        if(abs($from->getNumber() - $to->getNumber()) !== 1){
            return false;
        }

        return true;
    }

    /**
     * @param Notation $notation
     * @param Board $board
     * @param array|null $anychecks
     * @return bool
     */
    private function checkCheck(
        Notation $notation,
        Board $board,
        array $anychecks = null
    ): bool{
        $afterMoveBoard = $board->makeBoardOfChanges(false, $notation);
        $alreadyChecks = $anychecks ?? $board->anyChecks();
        $afterMoveChecks = $afterMoveBoard->anyChecks();

        $allowedBecauseChecks = true;

        $movingPiece = $board->getPieceFromPosition($notation->getFrom());
        assert($movingPiece instanceof Piece);

        if ($alreadyChecks !== []) {
            // There are checks before the move $notation. Now we got to check if that check is still there after the move.
            // If it is, then the move is not allowed.

            foreach ($afterMoveChecks as $afterMoveCheck) {
                [$checkingPiece, $checkedPiece] = $afterMoveCheck;
                assert($checkingPiece instanceof Position);
                assert($checkedPiece instanceof Position);

                foreach ($alreadyChecks as $alreadyCheck) {
                    [$alreadyCheckingPiece, $alreadyCheckedPiece] = $alreadyCheck;
                    assert($alreadyCheckingPiece instanceof Position);
                    assert($alreadyCheckedPiece instanceof Position);

                    if ($alreadyCheckingPiece->equals($checkingPiece) && $alreadyCheckedPiece->equals($checkedPiece)) {
                        $allowedBecauseChecks = false;
                    }
                }
            }
        }

        return $allowedBecauseChecks;
    }

    /**
     * @param Notation $notation
     * @param Board $board
     * @return bool
     */
    private function DIAGONAL_STEP(Notation $notation, Board $board): bool
    {
        $from = $notation->getFrom();
        $to = $notation->getTo();
        $piece = $board->getPieceFromPosition($from);

        assert($piece !== null);

        $toPiece = $board->getPieceFromPosition($to);

        if ($toPiece !== null) {
            return false;
        }

        // Check distance traveled for $letter to be 1
        if (Board::calculateLetter($from->getLetter(), 1) !== $to->getLetter() && Board::calculateLetter($from->getLetter(), -1) !== $to->getLetter()) {
            return false;
        }

        // Check distance traveled for $number to be 1
        if (abs($from->getNumber() - $to->getNumber()) !== 1) {
            return false;
        }

        return true;
    }

    /**
     * @param Notation $notation
     * @param Board $board
     * @return bool
     */
    private function DIAGONAL_ALL(Notation $notation, Board $board): bool
    {
        $from = $notation->getFrom();
        $to = $notation->getTo();
        $piece = $board->getPieceFromPosition($from);

        assert($piece !== null);

        $toPiece = $board->getPieceFromPosition($to);

        if ($toPiece !== null) {
            return false;
        }

        // Calculate all steps between $from and $to
        $positions = Board::walkDiagonalBetween($from, $to, $board);

        foreach ($positions as $position) {
            assert($position instanceof Position);

            if ($board->getPieceFromPosition($position) !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Notation $notation
     * @param Board $board
     * @return bool
     */
    private function DIAGONAL_ALL_TAKE(Notation $notation, Board $board): bool
    {
        $from = $notation->getFrom();
        $to = $notation->getTo();
        $piece = $board->getPieceFromPosition($from);

        assert($piece !== null);

        $toPiece = $board->getPieceFromPosition($to);

        if ($toPiece === null) {
            return false;
        }

        // Calculate all steps between $from and $to
        $positions = Board::walkDiagonalBetween($from, $to, $board);

        $count = 0;
        foreach ($positions as $position) {
            assert($position instanceof Position);

            if ($board->getPieceFromPosition($position) !== null) {
                $count++;
            }
        }

        return $count === 1 && $positions[count($positions) - 1]->equals($to);
    }

    /**
     * @param Notation $notation
     * @param Board $board
     * @return bool
     */
    private function LINEAR_ALL(Notation $notation, Board $board): bool
    {
        $from = $notation->getFrom();
        $to = $notation->getTo();
        $piece = $board->getPieceFromPosition($from);

        assert($piece !== null);

        $toPiece = $board->getPieceFromPosition($to);

        if ($toPiece === null) {
            return false;
        }

        // Calculate all steps between $from and $to
        $positions = Board::calculateStraightBetween($from, $to, $board);

        foreach ($positions as $position) {
            assert($position instanceof Position);

            if ($board->getPieceFromPosition($position) !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Notation $notation
     * @param Board $board
     * @return bool
     */
    private function LINEAR_ALL_TAKE(Notation $notation, Board $board): bool
    {
        $from = $notation->getFrom();
        $to = $notation->getTo();
        $piece = $board->getPieceFromPosition($from);

        assert($piece !== null);

        $toPiece = $board->getPieceFromPosition($to);

        if ($toPiece === null) {
            return false;
        }

        if ($toPiece->getColor() === $piece->getColor()) {
            return false;
        }

        // Calculate all steps between $from and $to
        $positions = Board::calculateStraightBetween($from, $to, $board);

        $count = 0;
        foreach ($positions as $position) {
            assert($position instanceof Position);


            if ($board->getPieceFromPosition($position) !== null) {
                $count++;
            }
        }

        return $count === 1 && $positions[count($positions) - 1]->equals($to);
    }

    /**
     * @param Notation $notation
     * @param Board $board
     * @return bool
     */
    private function LINEAR_STEP(Notation $notation, Board $board): bool
    {
        $from = $notation->getFrom();
        $to = $notation->getTo();
        $piece = $board->getPieceFromPosition($from);

        assert($piece !== null);

        $toPiece = $board->getPieceFromPosition($to);

        if ($toPiece !== null) {
            return false;
        }

        // Check distance traveled for $letter to be 1
        if (Board::calculateLetter($from->getLetter(), 1) !== $to->getLetter() && Board::calculateLetter($from->getLetter(), -1) !== $to->getLetter()) {
            return false;
        }

        // Check distance traveled for $number to be 1
        if (abs($from->getNumber() - $to->getNumber()) !== 1) {
            return false;
        }

        return true;
    }

    /**
     * @param Notation $notation
     * @param Board $board
     * @return bool
     */
    private function LINEAR_STEP_TAKE(Notation $notation, Board $board): bool
    {
        $from = $notation->getFrom();
        $to = $notation->getTo();
        $piece = $board->getPieceFromPosition($from);

        assert($piece !== null);

        $toPiece = $board->getPieceFromPosition($to);

        if ($toPiece === null) {
            return false;
        }

        // Check distance traveled for $letter to be 1
        if (Board::calculateLetter($from->getLetter(), 1) !== $to->getLetter() && Board::calculateLetter($from->getLetter(), -1) !== $to->getLetter()) {
            return false;
        }

        // Check distance traveled for $number to be 1
        if (abs($from->getNumber() - $to->getNumber()) !== 1) {
            return false;
        }

        return true;
    }

    /**
     * @param Notation $notation
     * @param Board $board
     * @return bool
     */
    private function KNIGHT_STEP(Notation $notation, Board $board): bool
    {
        $from = $notation->getFrom();
        $to = $notation->getTo();
        $piece = $board->getPieceFromPosition($from);

        assert($piece !== null);

        $toPiece = $board->getPieceFromPosition($to);

        if ($toPiece !== null) {
            return false;
        }

        // If distance for $letter is not 1 and $number 2 and for $letter 2 and $number 1 return false
        return $this->knight_move($from, $to);
    }

    /**
     * @param Notation $notation
     * @param Board $board
     * @return bool
     */
    private function KNIGHT_STEP_TAKE(Notation $notation, Board $board): bool
    {
        $from = $notation->getFrom();
        $to = $notation->getTo();
        $piece = $board->getPieceFromPosition($from);

        assert($piece !== null);

        $toPiece = $board->getPieceFromPosition($to);

        if ($toPiece === null) {
            return false;
        }

        if ($piece->getColor() === $toPiece->getColor()) {
            return false;
        }

        // If distance for $letter is not 1 and $number 2 and for $letter 2 and $number 1 return false
        return $this->knight_move($from, $to);
    }

    /**
     * @param Notation $notation
     * @param Board $board
     * @return bool
     */
    private function CASTLE(Notation $notation, Board $board): bool
    {
        $from = $notation->getFrom();
        $to = $notation->getTo();
        $piece = $board->getPieceFromPosition($from);

        assert($piece !== null);

        $toPiece = $board->getPieceFromPosition($to);

        if ($toPiece === null) {
            return false;
        }

        // Check distance traveled for $letter to be 2 or 3
        if (Board::calculateLetter($from->getLetter(), 2) !== $to->getLetter() && Board::calculateLetter($from->getLetter(), -2) !== $to->getLetter()) {
            if (Board::calculateLetter($from->getLetter(), 3) !== $to->getLetter() && Board::calculateLetter($from->getLetter(), -3) !== $to->getLetter()) {
                return false;
            }
        }

        $calculateStraight = Board::calculateStraightBetween($from, $to, $board);

        // If there is anything on the straight except for a king and rook return false
        foreach ($calculateStraight as $position) {
            assert($position instanceof Position);

            $piece = $board->getPieceFromPosition($position);

            if ($piece !== null) {
                if ($piece->getName() !== 'R' && $piece->getName() !== 'K') {
                    return false;
                }
            }
        }

        if ($toPiece->getName() !== 'R') {
            return false;
        }

        // Check distance traveled for $number to be 0
        return $from->getNumber() === $to->getNumber();
    }

    public function filterMoves(Board $board, Notation...$moves): array {
        $result = [];

        foreach ($moves as $move) {
            if ($this->isValidFor($move, $board)) {
                $result[] = $move;
            }
        }

        return $result;
    }

    public function getAllMovesForKing(Board $board, Position $position): array
    {
        $moves = [];

        $moves[] = new Notation($position, new Position($position->getLetter(), $position->getNumber() + 1));
        $moves[] = new Notation($position, new Position($position->getLetter(), $position->getNumber() - 1));
        $moves[] = new Notation($position, new Position(Board::calculateLetter($position->getLetter(), 1), $position->getNumber()));
        $moves[] = new Notation($position, new Position(Board::calculateLetter($position->getLetter(), -1), $position->getNumber()));
        $moves[] = new Notation($position, new Position(Board::calculateLetter($position->getLetter(), 1), $position->getNumber() + 1));
        $moves[] = new Notation($position, new Position(Board::calculateLetter($position->getLetter(), 1), $position->getNumber() - 1));
        $moves[] = new Notation($position, new Position(Board::calculateLetter($position->getLetter(), -1), $position->getNumber() + 1));
        $moves[] = new Notation($position, new Position(Board::calculateLetter($position->getLetter(), -1), $position->getNumber() - 1));

        return $moves;
    }

    public function getAllMovesForQueen(Board $board, Position $position): array
    {
        $moves = [];

        $moves = array_merge($moves, $this->getAllMovesForBishop($board, $position));
        $moves = array_merge($moves, $this->getAllMovesForRook($board, $position, 'R'));

        return $moves;
    }

    public function getAllMovesForBishop(Board $board, Position $position): array
    {
        $diagonals = Board::calculateDiagonals($position, $board);

        $moves = [];

        foreach ($diagonals as $diagonal) {
            assert($diagonal instanceof Notation);

            if ($this->isValidFor($diagonal, $board)) {
                $moves[] = $diagonal;
            }
        }

        return $moves;
    }

    public function getAllMovesForRook(Board $board, Position $position, string $override = null): array
    {
        $straights = Board::calculateStraights($position, $board);

        $moves = [];

        foreach ($straights as $straight) {
            assert($straight instanceof Notation);

            if ($this->isValidFor($straight, $board, $override ?? 'R')) {
                $moves[] = $straight;
            }
        }

        return $moves;
    }

    public function getAllMovesForKnight(Board $board, Position $position): array
    {
        $moves = [];

        $moves[] = new Notation($position, new Position(Board::calculateLetter($position->getLetter(), 1), $position->getNumber() + 2));
        $moves[] = new Notation($position, new Position(Board::calculateLetter($position->getLetter(), 1), $position->getNumber() - 2));
        $moves[] = new Notation($position, new Position(Board::calculateLetter($position->getLetter(), -1), $position->getNumber() + 2));
        $moves[] = new Notation($position, new Position(Board::calculateLetter($position->getLetter(), -1), $position->getNumber() - 2));
        $moves[] = new Notation($position, new Position(Board::calculateLetter($position->getLetter(), 2), $position->getNumber() + 1));
        $moves[] = new Notation($position, new Position(Board::calculateLetter($position->getLetter(), 2), $position->getNumber() - 1));
        $moves[] = new Notation($position, new Position(Board::calculateLetter($position->getLetter(), -2), $position->getNumber() + 1));
        $moves[] = new Notation($position, new Position(Board::calculateLetter($position->getLetter(), -2), $position->getNumber() - 1));

        return $moves;
    }

    public function getAllMovesForPawn(Board $board, Position $position): array
    {
        $moves = [];

        $moves[] = new Notation($position, new Position($position->getLetter(), $position->getNumber() + 1));
        $moves[] = new Notation($position, new Position($position->getLetter(), $position->getNumber() - 1));
        $moves[] = new Notation($position, new Position(Board::calculateLetter($position->getLetter(), 1), $position->getNumber()));
        $moves[] = new Notation($position, new Position(Board::calculateLetter($position->getLetter(), -1), $position->getNumber()));
        $moves[] = new Notation($position, new Position(Board::calculateLetter($position->getLetter(), 1), $position->getNumber() + 1));
        $moves[] = new Notation($position, new Position(Board::calculateLetter($position->getLetter(), 1), $position->getNumber() - 1));
        $moves[] = new Notation($position, new Position(Board::calculateLetter($position->getLetter(), -1), $position->getNumber() + 1));
        $moves[] = new Notation($position, new Position(Board::calculateLetter($position->getLetter(), -1), $position->getNumber() - 1));

        return $moves;
    }

    public function getAllMovesForPiece(Board $board, Position $position): array
    {
        $piece = $board->getPieceFromPosition($position);

        if ($piece === null) {
            throw new \InvalidArgumentException('No piece at position ' . $position->getLetter() . $position->getNumber());
        }

        $movesFor = match ($piece->getName()) {
            'K' => $this->getAllMovesForKing($board, $position),
            'Q' => $this->getAllMovesForQueen($board, $position),
            'B' => $this->getAllMovesForBishop($board, $position),
            'R' => $this->getAllMovesForRook($board, $position),
            'N' => $this->getAllMovesForKnight($board, $position),
            'P' => $this->getAllMovesForPawn($board, $position),
            default => throw new \InvalidArgumentException('Unknown piece ' . $piece->getName()),
        };

        return $this->filterMoves($board, ...$movesFor);
    }

    /**
     * @param Position $from
     * @param Position $to
     * @return bool
     */
    private function knight_move(Position $from, Position $to): bool{
        if(abs($from->getNumber() - $to->getNumber()) !== 2 || (Board::calculateLetter($from->getLetter(), 1) !== $to->getLetter() && Board::calculateLetter($from->getLetter(), -1) !== $to->getLetter())){
            if(abs($from->getNumber() - $to->getNumber()) !== 1 || (Board::calculateLetter($from->getLetter(), 2) !== $to->getLetter() && Board::calculateLetter($from->getLetter(), -2) !== $to->getLetter())){
                return false;
            }
        }


        return true;
    }
}
