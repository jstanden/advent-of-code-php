<?php // @jeff@phpc.social
/** @noinspection PhpUnused */
/** @noinspection DuplicatedCode */

declare(strict_types=1);
namespace jstanden\AoC\Library\Grid2d;

class Vector2d
{
    public function __construct(
        public int $x,
        public int $y
    ) {}

    static function add(Vector2d $a, Vector2d $b): Vector2d {
        return new Vector2d($a->x + $b->x, $a->y + $b->y);
    }

    public function toString(): string {
        return sprintf('%d,%d', $this->x, $this->y);
    }
}