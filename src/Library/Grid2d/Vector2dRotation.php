<?php // @jeff@phpc.social
declare(strict_types=1);
namespace jstanden\AoC\Library\Grid2d;

enum Vector2dRotation : string {
	case LEFT = 'L';
	case RIGHT = 'R';
	case FLIP = 'F';
}
