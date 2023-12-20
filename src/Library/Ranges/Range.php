<?php // Jeff Standen <https://phpc.social/@jeff>
declare(strict_types=1);

namespace jstanden\AoC\Library\Ranges;

class Range {
	public int $bounds_lower = 0;
	public int $bounds_upper = 0;
	
	function __construct(int $lower, int $upper)
	{
		$this->bounds_upper = $upper;
		$this->bounds_lower = $lower;
	}
	
	function __toString(): string
	{
		return sprintf("%d-%d",
			$this->bounds_lower,
			$this->bounds_upper
		);
	}
	
	public function lengthInclusive() : int
	{
		if($this->bounds_lower == 0 && $this->bounds_upper == 0)
			return 0;
		
		return $this->bounds_upper - $this->bounds_lower + 1;
	}
	
	public function contains(int $at) : bool {
		return ($at >= $this->bounds_lower && $at <= $this->bounds_upper);
	}
	
	// Keep everything before inclusive $at and return after as a new range
	public function splitAfter(int $at) : ?Range
	{
		// If our range doesn't include this number, or it's the upper bound
		if(!$this->contains($at) || $at == $this->bounds_upper)
			return null;
		
		$range_remainder = clone $this;
		$range_remainder->bounds_lower = $at + 1;
		
		$this->bounds_upper = $at;
		
		return $range_remainder;
	}
	
	// Keep everything after inclusive $at and return before as a new range
	public function splitBefore(int $at) : ?Range
	{
		// If our range doesn't include this number, or it's the lower bound
		if(!$this->contains($at) || $at == $this->bounds_lower)
			return null;
		
		$range_remainder = clone $this;
		$range_remainder->bounds_upper = $at - 1;
		
		$this->bounds_lower = $at;
		
		return $range_remainder;
	}
}