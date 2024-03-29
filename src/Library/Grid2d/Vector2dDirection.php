<?php // @jeff@phpc.social
declare(strict_types=1);
namespace jstanden\AoC\Library\Grid2d;

enum Vector2dDirection: string
{
    case NORTHWEST = 'northwest';
    case NORTH = 'north';
    case NORTHEAST = 'northeast';
    case WEST = 'west';
    case EAST = 'east';
    case SOUTHWEST = 'southwest';
    case SOUTH = 'south';
    case SOUTHEAST = 'southeast';

	public function getVector(): Vector2d
    {
        return new Vector2d(...match ($this) {
            self::NORTHWEST => [-1, -1],
            self::NORTH => [0, -1],
            self::NORTHEAST => [1, -1],
            self::WEST => [-1, 0],
            self::EAST => [1, 0],
            self::SOUTHWEST => [-1, 1],
            self::SOUTH => [0, 1],
            self::SOUTHEAST => [1, 1],
        });
    }

	public function fromVector(Vector2d $vector): Vector2dDirection {
		return match((string)$vector) {
			'-1,-1' => self::NORTHWEST,
			'0,-1' => self::NORTH,
			'1,-1' => self::NORTHEAST,
			'-1,0' => self::WEST,
			'1,0' => self::EAST,
			'-1,1' => self::SOUTHWEST,
			'0,1' => self::SOUTH,
			'1,1' => self::SOUTHEAST,
		};
	}
	
	public static function between(mixed $from, mixed $to) : Vector2dDirection
	{
		return match(true) {
			$to->x > $from->x => self::EAST,
			$to->x < $from->x => self::WEST,
			$to->y > $from->y => self::NORTH,
			default => self::SOUTH,
		};
	}

	public function rotate(Vector2dRotation $rotation): Vector2dDirection {
		return $this->fromVector($this->getVector()->rotate($rotation));
	}

	public function isVertical() : bool {
		return in_array($this, [Vector2dDirection::NORTH, Vector2dDirection::SOUTH]);
	}

	public function isHorizontal() : bool {
		return in_array($this, [Vector2dDirection::EAST, Vector2dDirection::WEST]);
	}

	public function isDiagonal() : bool {
		return in_array($this, [
			Vector2dDirection::NORTHWEST,
			Vector2dDirection::NORTHEAST,
			Vector2dDirection::SOUTHWEST,
			Vector2dDirection::SOUTHEAST,
		]);
	}
}