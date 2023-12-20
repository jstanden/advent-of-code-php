<?php // @jeff@phpc.social
/** @noinspection DuplicatedCode */
declare(strict_types=1);

namespace AoC\Year2023\Day19;

use jstanden\AoC\Library\Ranges\Range;

require_once('../../vendor/autoload.php');

class WorkflowRule {
	public function __construct(
		public string $name='',
		public string $oper='',
		public int $value=0,
		public string $action=''
	) {}
	
	// Return a WorkflowRule from a rule pattern
	static function factory(string $pattern) : WorkflowRule {
		// If we have a complex rule, parse its components
		if(str_contains($pattern, ':')) {
			$matches = [];
			sscanf($pattern, '%[a-z]%[<>]%d:%[A-Za-z]', // a<2006:qkq
				$matches['name'], $matches['oper'], $matches['value'], $matches['action']
			);
			return new WorkflowRule(...$matches);
		
		// otherwise it's another workflow or an end state action
		} else {
			return new WorkflowRule('','',0, $pattern);
		}
	}
}

// Example: px{a<2006:qkq,m>2090:A,rfg}
function parseWorkflows(array $instructions) : array {
	// For each instruction line
	$workflows = array_map(
		function($instruction) {
			// Split the rule name and rule set
			[$name, $rules] = explode('{', rtrim($instruction,'}'));
			// Instance rules from patterns
			$rules = array_map(
				fn($pattern) => WorkflowRule::factory($pattern),
				explode(',', $rules) // rule sets are comma-delimited
			);
			return [ 'name' => $name, 'rules' => $rules ];
		},
		$instructions
	);
	
	// Return name=>workflow map
	return array_combine(
		array_column($workflows, 'name'),
		$workflows
	);
}

// Example: {x=787,m=2655,a=1222,s=2876}
function parseParts(array $schemas) : array {
	// For each part schema
	return array_map(
		function($schema) {
			// Build a set of (key,value) tuples
			$ratings = array_map(
				fn($pair) => explode('=', $pair),
				explode(',', trim($schema,'{}'))
			);
			
			// Return a key=>value map
			return array_combine(
				array_column($ratings, 0),
				array_column($ratings, 1)
			);
		},
		$schemas
	);
}

$lines = explode("\n", file_get_contents('../../data/2023/19/data.txt'));

$split_at = array_search("", $lines); // split workflows/parts on the blank line
$workflows = parseWorkflows(array_slice($lines, 0, $split_at));
$parts = parseParts(array_slice($lines, $split_at+1));

// ===============================================================
// Part 1: 353553

function executeWorkflow(array $workflow, array $part) : ?string {
	foreach($workflow['rules'] as $rule) {
		// If the workflow rule passes
		if(
			null == $rule->oper
			|| '>' == $rule->oper && $part[$rule->name] > $rule->value
			|| '<' == $rule->oper && $part[$rule->name] < $rule->value
		// Run the associated action
		) return $rule->action;
	}
	return null;
}

echo "Part 1: ", array_reduce($parts, function(int $sum, array $part) use (&$workflows) {
	$result = 'in';
	
	while(!in_array($result, ['A','R']))
		$result = executeWorkflow($workflows[$result], $part);
	
	return $sum + (('A' == $result) ? array_sum($part) : 0);
}, 0), PHP_EOL;

// ===============================================================
// Part 2: 124615747767410

class HypotheticalPart {
	private array $ranges;
	
	// A hypothetical part exists in multiple possible states based on ranges
	public function __construct() {
		$this->ranges = [
			'x' => new Range(1, 4000),
			'm' => new Range(1, 4000),
			'a' => new Range(1, 4000),
			's' => new Range(1, 4000),
		];
	}
	
	public function __clone(): void {
		foreach(array_keys($this->ranges) as $k)
			$this->ranges[$k] = clone $this->ranges[$k];
	}
	
	// Split the current part at the given key/location
	public function split(string $mode, string $key, int $at) : ?HypotheticalPart {
		$new_part = clone $this;
		
		// Split the range into two
		if(null == ($new_range = $mode == 'before'
			? $this->ranges[$key]->splitBefore($at)
			: $this->ranges[$key]->splitAfter($at)
			)) return null;
		
		// Return a new hypothetical part with the remainder
		$new_part->ranges[$key] = $new_range;
		return $new_part;
	}
	
	public function getCombinations() : int {
		// Total combinations is the product of (x,m,a,s) range lengths
		return array_product(
			array_map(fn($range) => $range->lengthInclusive(), $this->ranges)
		);
	}
}

// Recurse through the possible paths of a hypothetical part given a set of rules
function getHypotheticalPartAcceptedCombinations(HypotheticalPart $part, array $rules) : int {
	global $workflows;
	
	// Get and consume the next rule from the queue
	$rule = array_shift($rules);
	
	// If we're performing an action in this rule
	if(in_array($rule->oper, ['>','<'])) {
		// Fork the hypothetical parts into new ranges
		$part_remainder = $part->split(
			mode: '>' == $rule->oper ? 'after' : 'before',
			key: $rule->name,
			at: $rule->value
		);
		
		// Our matching range immediately routes to the new workflow or end state
		$new_rule = new WorkflowRule('','',0, $rule->action);
		
		return
			($part_remainder ? getHypotheticalPartAcceptedCombinations($part_remainder, [$new_rule]) : 0)
			// The non-matching range continues to the next rule
			+ getHypotheticalPartAcceptedCombinations($part, $rules)
		;
		
	// Accepted!
	} else if('A' == $rule->action) {
		return $part->getCombinations();
		
	// Rejected
	} else if('R' == $rule->action) {
		return 0;
		
	// We're routing to a new workflow
	} else if($rule->action) {
		return getHypotheticalPartAcceptedCombinations($part, $workflows[$rule->action]['rules'] ?? []);
	}
	
	return 0;
}

// 124615747767410
echo "Part 2: ", getHypotheticalPartAcceptedCombinations(
	new HypotheticalPart(),
	$workflows['in']['rules']
), PHP_EOL;