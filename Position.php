<?php
namespace RealChess;

class Position{
    public function __construct(private string $letter, private int $number){
        $this->letter = strtolower($letter);
    }

    public static function generateFromString(string $position): Position{
        return new Position($position[0], (int)substr($position, 1));
    }

    /**
     * @return int
     */
    public function getNumber(): int{
        return $this->number;
    }

    public function equals(self $position): bool {
        return $this->getLetter() === $position->getLetter() && $this->getNumber() === $position->getNumber();
    }

    /**
     * @param int $number
     */
    public function setNumber(int $number): void{
        $this->number = $number;
    }

    public function __toString(): string {
        return $this->letter . $this->number;
    }

    /**
     * @return string
     */
    public function getLetter(): string{
        return $this->letter;
    }

    /**
     * @param string $letter
     */
    public function setLetter(string $letter): void{
        $this->letter = $letter;
    }
}
