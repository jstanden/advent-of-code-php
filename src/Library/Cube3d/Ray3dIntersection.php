<?php // @jeff@phpc.social
declare(strict_types=1);

namespace jstanden\AoC\Library\Cube3d;

class Ray3dIntersection {
	function __construct(
		public Ray3d $ray_a,
		public Ray3d $ray_b,
		public Vector3d $at,
		public float $time_a,
		public float $time_b,
	) {}
}
