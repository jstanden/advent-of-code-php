<?php // @jeff@phpc.social
declare(strict_types=1);

namespace jstanden\AoC\Library\Cube3d;

class Ray3d {
	public function __construct(
		public Vector3d $origin,
		public Vector3d $direction
	) {}

	public function __toString() {
		return sprintf("%d,%d,%d @ %d,%d,%d",
			$this->origin->x,
			$this->origin->y,
			$this->origin->z,
			$this->direction->x,
			$this->direction->y,
			$this->direction->z,
		);
	}
	
	public function __clone() {
		$this->origin = clone $this->origin;
		$this->direction = clone $this->direction;
	}

	function positionAtTime(int $time) : Vector3d {
		return $this->origin->add($this->direction->multiply(new Vector3d($time, $time, $time)));
	}
	
	function intersects2d(
		Ray3d $ray,
		string $projection_axis = 'z',
	) : ?Ray3dIntersection {
		// Handle projections: X=YZ, Y=XZ, Z=XY
		if('x' == $projection_axis) {
			$axis1 = 'y';
			$axis2 = 'z';
		} else if('y' == $projection_axis) {
			$axis1 = 'x';
			$axis2 = 'z';
		} else {
			$axis1 = 'x';
			$axis2 = 'y';
		}
		
		$cross_product = $this->direction->getAxis($axis1) * $ray->direction->getAxis($axis2)
			- $this->direction->getAxis($axis2) * $ray->direction->getAxis($axis1);

		// Parallel?
		if(abs($cross_product) < 1e-6)
			return null;

		$t1 = (
			($ray->origin->getAxis($axis1) - $this->origin->getAxis($axis1)) * $ray->direction->getAxis($axis2)
			- ($ray->origin->getAxis($axis2) - $this->origin->getAxis($axis2)) * $ray->direction->getAxis($axis1))
			/ $cross_product
		;

		$t2 = (
			($ray->origin->getAxis($axis1) - $this->origin->getAxis($axis1)) * $this->direction->getAxis($axis2)
			- ($ray->origin->getAxis($axis2) - $this->origin->getAxis($axis2)) * $this->direction->getAxis($axis1))
			/ $cross_product
		;

		if($t1 >= 0 && $t2 >= 0) {
			if('x' == $projection_axis) {
				$at = new Vector3d(
					$this->origin->getAxis($projection_axis) + $t1 * $this->direction->getAxis($projection_axis),
					$this->origin->getAxis($axis1) + $t1 * $this->direction->getAxis($axis1),
					$this->origin->getAxis($axis2) + $t1 * $this->direction->getAxis($axis2)
				);
			} else if('y' == $projection_axis) {
				$at = new Vector3d(
					$this->origin->getAxis($axis1) + $t1 * $this->direction->getAxis($axis1),
					$this->origin->getAxis($projection_axis) + $t1 * $this->direction->getAxis($projection_axis),
					$this->origin->getAxis($axis2) + $t1 * $this->direction->getAxis($axis2)
				);
			} else {
				$at = new Vector3d(
					$this->origin->getAxis($axis1) + $t1 * $this->direction->getAxis($axis1),
					$this->origin->getAxis($axis2) + $t1 * $this->direction->getAxis($axis2),
					$this->origin->getAxis($projection_axis) + $t1 * $this->direction->getAxis($projection_axis)
				);
			}
			
			return new Ray3dIntersection($this, $ray, $at, $t1, $t2);
			
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