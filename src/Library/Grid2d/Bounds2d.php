<?php // @jeff@phpc.social
declare(strict_types=1);
namespace jstanden\AoC\Library\Grid2d;

class Bounds2d extends Entity2d {
    function __construct(
        public Vector2d $origin, public int $width, public int $height=1
    ) {
        parent::__construct('bounds', $this->origin, $this->width, $this->height);
    }
}