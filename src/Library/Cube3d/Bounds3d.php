<?php // @jeff@phpc.social
declare(strict_types=1);

namespace jstanden\AoC\Library\Cube3d;

class Bounds3d {
	function __construct(
		public Vector3d $origin, public Vector3d $extent
	) {}
	
	function extents() : array {
		return [
			'min_x' => min([$this->origin->x, $this->extent->x]),
			'max_x' => max([$this->origin->x, $this->extent->x]),
			'min_y' => min([$this->origin->y, $this->extent->y]),
			'max_y' => max([$this->origin->y, $this->extent->y]),
			'min_z' => min([$this->origin->z, $this->extent->z]),
			'max_z' => max([$this->origin->z, $this->extent->z]),
		];
	}
	
	function overlaps(Bounds3d $entity): bool
	{
		$a_extents = $this->extents();
		$b_extents = $entity->extents();
		
		return !(
			$a_extents['max_x'] < $b_extents['min_x']
			|| $b_extents['max_x'] < $a_extents['min_x']
			|| $a_extents['max_y'] < $b_extents['min_y']
			|| $b_extents['max_y'] < $a_extents['min_y']
			|| $a_extents['max_z'] < $b_extents['min_z']
			|| $b_extents['max_z'] < $a_extents['min_z']
		);
	}
}