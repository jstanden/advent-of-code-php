<?php // @jeff@phpc.social
declare(strict_types=1);

namespace jstanden\AoC\Library\Cube3d;

class Vector3d {
	public function __construct(
		public float $x,
		public float $y,
		public float $z
	) {
	
	}
	
	public static function fromString(string $string) : Vector3d
	{
		return new Vector3d(
			...array_map('floatval', explode(',', $string, 3))
		);
	}
	
	public static function distance(Vector3d $origin, Vector3d $target) : Vector3d
	{
		return new Vector3d(
			$target->x - $origin->x,
			$target->y - $origin->y,
			$target->z - $origin->z,
		);
	}
	
	public static function direction(Vector3d $origin, Vector3d $target) : Vector3d
	{
		$distance = self::distance($origin, $target);
		$m = $distance->magnitude();
		
		if(0 == $m) return new Vector3d(0,0,0);
		
		return new Vector3d($distance->x/$m, $distance->y/$m, $distance->z/$m);
	}
	
	public function __toString(): string
	{
		return sprintf("%g,%g,%g", $this->x, $this->y, $this->z);
	}
	
	public function equals(Vector3d $other) : bool
	{
		return $this->x == $other->x && $this->y == $other->y && $this->z == $other->z;
	}
	
	public function add(Vector3d $vector) : Vector3d
	{
		return new Vector3d(
			$this->x + $vector->x,
			$this->y + $vector->y,
			$this->z + $vector->z,
		);
	}
	
	public function magnitude() : float
	{
		return sqrt($this->x**2 + $this->y**2 + $this->z**2);
	}
}