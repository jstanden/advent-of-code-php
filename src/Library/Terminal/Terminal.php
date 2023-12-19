<?php // Jeff Standen <https://phpc.social/@jeff>
declare(strict_types=1);

namespace jstanden\AoC\Library\Terminal;

class Terminal {
	static function clear() : void {
		echo "\e[H\e[J";
	}
}