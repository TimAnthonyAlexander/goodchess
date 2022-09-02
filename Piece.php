<?php
namespace RealChess;


use Ramsey\Uuid\Uuid;

class Piece{
    private string $uuid;
    private Position $pos;
    private string $name;
    private bool $color; // True is white, False is black

    /**
     * @return string
     */
    public function getName(): string{
        return $this->name;
    }

    public function __toString(): string{
        return match($this->name){
            "K" => "King",
            "Q" => "Queen",
            "R" => "Rook",
            "B" => "Bishop",
            "N" => "Knight",
            "P" => "Pawn",
            default => "Unknown"
        };
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void{
        $this->name = $name;
    }

    public function __construct(array $data) {
        $this->uuid = $data['uuid'];
        $this->name = $data['piece'];
        $this->color = $data['color'] ?? true;
    }

    /**
     * @return string
     */
    public function getUuid(): string{
        return $this->uuid;
    }

    /**
     * @param string $uuid
     */
    public function setUuid(string $uuid): void{
        $this->uuid = $uuid;
    }

    /**
     * @return Position
     */
    public function getPos(): Position{
        return $this->pos;
    }

    /**
     * @param Position $pos
     */
    public function setPos(Position $pos): void{
        $this->pos = $pos;
    }

    /**
     * @return bool
     */
    public function getColor(): bool{
        return $this->color;
    }

    /**
     * @param bool $color
     */
    public function setColor(bool $color): void{
        $this->color = $color;
    }

    public function jsonSerialize(): array {
        return [
            'uuid' => $this->uuid,
            'piece' => $this->name,
            'color' => $this->color,
        ];
    }
}
