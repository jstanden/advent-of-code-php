<?php // @jeff@phpc.social
declare(strict_types=1);
namespace jstanden\AoC\Library\Collections;

class MinPriorityQueue extends \SplPriorityQueue {
	function compare(mixed $priority1, mixed $priority2): int {
		return $priority2 <=> $priority1;
	}
}
