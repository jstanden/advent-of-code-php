<?php // @jeff@phpc.social
/** @noinspection PhpUnused */
/** @noinspection DuplicatedCode */

declare(strict_types=1);
namespace jstanden\AoC\Library\Grid2d;

class Vector2d
{
    public function __construct(
        public float $x,
        public float $y
    ) {}

	public function __toString(): string
	{
		return sprintf("%g,%g", $this->x, $this->y);
	}
	
	public function equals(Vector2d $other) : bool
	{
		return $this->x == $other->x && $this->y == $other->y;
	}
	
	static function add(Vector2d $a, Vector2d $b): Vector2d
	{
        return new Vector2d($a->x + $b->x, $a->y + $b->y);
    }

	static function subtract(Vector2d $a, Vector2d $b): Vector2d
	{
        return new Vector2d($a->x - $b->x, $a->y - $b->y);
    }

	function multiply(Vector2d $b) : Vector2d
	{
        return new Vector2d($this->x * $b->x, $this->y * $b->y);
	}

	public function set(float $x, float $y) : Vector2d
	{
		$this->x = $x;
		$this->y = $y;
		return $this;
	}

	public function rotate(Vector2dRotation $rotation, ?Vector2d $origin=null) : Vector2d
	{
		if(is_null($origin))
			$origin = new Vector2d(0,0);

		if($rotation == Vector2dRotation::RIGHT) {
			$rotation_matrix = [[0, -1], [1, 0]];
		} else if($rotation == Vector2dRotation::LEFT) {
			$rotation_matrix = [[0, 1], [-1, 0]];
		} else {
			$rotation_matrix = [[-1, 0], [0, -1]];
		}

		$x_translated = $this->x - $origin->x;
		$y_translated = $this->y - $origin->y;

		$x_rotated = $rotation_matrix[0][0] * $x_translated + $rotation_matrix[0][1] * $y_translated;
		$y_rotated = $rotation_matrix[1][0] * $x_translated + $rotation_matrix[1][1] * $y_translated;

		$this->x = $x_rotated + $origin->x;
		$this->y = $y_rotated + $origin->y;

		return $this;
	}
}