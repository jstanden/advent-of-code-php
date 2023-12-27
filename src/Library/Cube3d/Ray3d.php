<?php // @jeff@phpc.social
/** @noinspection DuplicatedCode */
declare(strict_types=1);

namespace jstanden\AoC\Library\Cube3d;

class Ray3d {
	public function __construct(
		public Vector3d $origin,
		public Vector3d $direction
	) {}

	public function __clone() {
		$this->origin = clone $this->origin;
		$this->direction = clone $this->direction;
	}

	function intersects2d(
		Ray3d $ray
	) : ?Vector3d {
		$cross_product = $this->direction->x * $ray->direction->y
			- $this->direction->y * $ray->direction->x;

		// Parallel?
		if(abs($cross_product) < 1e-6)
			return null;

		$t1 = (
			($ray->origin->x - $this->origin->x) * $ray->direction->y
			- ($ray->origin->y - $this->origin->y) * $ray->direction->x)
			/ $cross_product
		;

		$t2 = (
			($ray->origin->x - $this->origin->x) * $this->direction->y
			- ($ray->origin->y - $this->origin->y) * $this->direction->x)
			/ $cross_product
		;

		if($t1 >= 0 && $t2 >= 0) {
			return new Vector3d(
				$this->origin->x + $t1 * $this->direction->x,
				$this->origin->y + $t1 * $this->direction->y,
				$this->origin->z + $t1 * $this->direction->z
			);
		} else {
			return null;
		}
	}

	function intersects3d(
		Ray3d $ray
	) : ?Vector3d {
		$cross_product_x = $this->direction->y * $ray->direction->z - $this->direction->z * $ray->direction->y;
		$cross_product_y = $this->direction->z * $ray->direction->x - $this->direction->x * $ray->direction->z;
		$cross_product_z = $this->direction->x * $ray->direction->y - $this->direction->y * $ray->direction->x;

		$cross_product = sqrt(
			$cross_product_x**2 + $cross_product_y**2 + $cross_product_z**2
		);

		var_dump($cross_product);

		// Parallel?
		if(abs($cross_product) < 1e-6)
			return null;

		$t1 = (
			($ray->origin->x - $this->origin->x) * $ray->direction->y
			- ($ray->origin->y - $this->origin->y) * $ray->direction->x)
			/ $cross_product
		;

		$t2 = (
			($ray->origin->x - $this->origin->x) * $this->direction->y
			- ($ray->origin->y - $this->origin->y) * $this->direction->x)
			/ $cross_product
		;

		if($t1 >= 0 && $t2 >= 0) {
			return new Vector3d(
				$this->origin->x + $t1 * $this->direction->x,
				$this->origin->y + $t1 * $this->direction->y,
				$this->origin->z + $t1 * $this->direction->z,
			);
		} else {
			return null;
		}
	}
}