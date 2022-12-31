<?php
declare(strict_types=1);

namespace AoC\Year2015\Day15\Part1;

class Ingredient {
	public function __construct(
		public string $name,
		public int $capacity,
		public int $durability,
		public int $flavor,
		public int $texture,
		public int $calories
	) {}
}

class RecipeQuantity {
	public function __construct(
		public Ingredient $ingredient,
		public int $quantity
	) {}
}

class Recipe {
	public function __construct(
		public array $mix = []
	) {}
	
	public function score() : int {
		$capacity = $durability = $flavor = $texture = 0;
		
		foreach($this->mix as $part) { /** @var RecipeQuantity $part */
			$capacity += $part->quantity * $part->ingredient->capacity;
			$durability += $part->quantity * $part->ingredient->durability;
			$flavor += $part->quantity * $part->ingredient->flavor;
			$texture += $part->quantity * $part->ingredient->texture;
		}
		
		return array_product([
			max($capacity, 0),
			max($durability, 0),
			max($flavor, 0),
			max($texture, 0),
		]);
	}
	
	public function calories() : int {
		return array_sum(array_map(fn($part) => $part->quantity * $part->ingredient->calories, $this->mix));
	}
}

$ingredients = array_map(
	function($line) {
		$properties = [];
		sscanf($line, "%[A-za-z]: capacity %d, durability %d, flavor %d, texture %d, calories %d",
			$properties['name'],
			$properties['capacity'],
			$properties['durability'],
			$properties['flavor'],
			$properties['texture'],
			$properties['calories'],
		);
		return new Ingredient(...$properties);
	},
//	explode("\n", file_get_contents('./data/example.txt'))
	explode("\n", file_get_contents('./data/input.txt'))
);

$best_part1_score = $best_part2_score = PHP_INT_MIN;
$best_part1_mix = $best_part2_mix = [];

// This could have been more sophisticated
for($i=0;$i<=100;$i++) {
	for($j=0;$j<=100;$j++) {
		for($k=0;$k<=100;$k++) {
			for($l=0;$l<=100;$l++) {
				// Sum the coefficients
				$sum = $i + $j + $k + $l;
				
				// Only for combinations that add up to exactly 100
				if (100 == $sum) {
					$recipe = new Recipe([
						new RecipeQuantity($ingredients[0], $i),
						new RecipeQuantity($ingredients[1], $j),
						new RecipeQuantity($ingredients[2], $k),
						new RecipeQuantity($ingredients[3], $l),
					]);
					$score = $recipe->score();
					
					// Part 1
					if($score > $best_part1_score) {
						$best_part1_score = $score;
						$best_part1_mix = [$i, $j, $k, $l];
					}
					
					// Part 2
					if($score > $best_part2_score && 500 == $recipe->calories()) {
						$best_part2_score = $score;
						$best_part2_mix = [$i, $j, $k, $l];
					}
					
				} elseif($sum > 100) {
					break;
				}
			}
		}
	}
}

printf("Part 1: %d (%s)\n", $best_part1_score, implode(', ', $best_part1_mix)); // 18965440 (24, 29, 31, 16)
printf("Part 2: %d (%s)\n", $best_part2_score, implode(', ', $best_part2_mix)); // 15862900 (21, 23, 31, 25)
