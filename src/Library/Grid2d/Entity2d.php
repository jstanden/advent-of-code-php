<?php // @jeff@phpc.social
declare(strict_types=1);
namespace jstanden\AoC\Library\Grid2d;

class Entity2d
{
    function __construct(
        public string $name, public Vector2d $origin, public int $width, public int $height = 1
    )
    {
    }

    function getExtent(): Vector2d
    {
        return Vector2d::add($this->origin, new Vector2d($this->width - 1, $this->height - 1));
    }

    function overlaps(Entity2d $entity): bool
    {
        return !(
            $entity->getExtent()->x < $this->origin->x
            || $this->getExtent()->x < $entity->origin->x
            || $entity->getExtent()->y < $this->origin->y
            || $this->getExtent()->y < $entity->origin->y
        );
    }
}