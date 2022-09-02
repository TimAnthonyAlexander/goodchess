<?php
namespace RealChess;


class Rules{
    public const KING_RULES = ["CASTLE", "DIAGONAL_STEP", "LINEAR_STEP", "DIAGONAL_STEP_TAKE", "LINEAR_STEP_TAKE", "NO_CHECK"];

    public const QUEEN_RULES = ["DIAGONAL_ALL", "LINEAR_ALL", "DIAGONAL_ALL_TAKE", "LINEAR_ALL_TAKE"];

    public const ROOK_RULES = ["LINEAR_ALL", "LINEAR_ALL_TAKE"];

    public const BISHOP_RULES = ["DIAGONAL_ALL", "DIAGONAL_ALL_TAKE"];

    public const KNIGHT_RULES = ["KNIGHT_STEP", "KNIGHT_STEP_TAKE"];

    public const PAWN_RULES = ["PAWN_STEP", "PAWN_DOUBLE_STEP", "DIAGONAL_STEP_TAKE", "PAWN_EN_PASSANT"];

    public function isValidFor(Notation $notation, Board $board): bool{
        $piece = $board->getPieceFromPosition($notation->getFrom());

        if($piece === null){
            return false;
        }

        if($notation->getFrom()->getLetter() === $notation->getTo()->getLetter() && $notation->getFrom()->getNumber() === $notation->getTo()->getNumber()){
            return false;
        }

        $rules = match ($piece->getName()) {
            "K" => self::KING_RULES,
            "Q" => self::QUEEN_RULES,
            "R" => self::ROOK_RULES,
            "B" => self::BISHOP_RULES,
            "N" => self::KNIGHT_RULES,
            "P" => self::PAWN_RULES,
        };

        foreach($rules as $rule){
            if($this->checkRule($notation, $board, $rule)){
                print "Move [" . $notation . "] valid by rule [$rule]." . PHP_EOL;
                return true;
            }
        }

        return false;
    }

    public function checkRule(Notation $notation, Board $board, string $ruleName): bool{
        if(!method_exists($this, $ruleName)){
            print "Rule $ruleName does not exist" . PHP_EOL;
            return false;
        }

        $anyChecks = $board->anyChecks();

        if ($anyChecks !== []) {
            foreach ($anyChecks as $anyCheck) {
                [$checkingPiecePos, $checkedPiecePos] = $anyCheck;
                $checkingPiece = $board->getPieceFromPosition($checkingPiecePos);
                $checkedPiece = $board->getPieceFromPosition($checkedPiecePos);

                print "Check!".PHP_EOL;
                print "Checking piece: " . $checkingPiece . PHP_EOL;
                print "Checked piece: " . $checkedPiece . PHP_EOL;
            }
        }

        if (!$this->checkCheck($notation, $board, $anyChecks)) {
            return false;
        }

        return $this->$ruleName($notation, $board);
    }

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

    private function DIAGONAL_ALL(Notation $notation, Board $board): bool
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

        foreach ($positions as $position) {
            assert($position instanceof Position);

            if ($board->getPieceFromPosition($position) !== null) {
                return false;
            }
        }

        return true;
    }

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

        return $count === 1;
    }

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

        // Calculate all steps between $from and $to
        $positions = Board::calculateStraightBetween($from, $to, $board);

        $count = 0;
        foreach ($positions as $position) {
            assert($position instanceof Position);

            if ($board->getPieceFromPosition($position) !== null) {
                $count++;
            }
        }

        return $count === 1;
    }

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


}
