<?php // @jeff@phpc.social
/** @noinspection DuplicatedCode */
declare(strict_types=1);

namespace AoC\Year2023\Day20;

use jstanden\AoC\Library\Math\Math;

require_once('../../vendor/autoload.php');

enum Pulse {
	case LOW;
	case HIGH;
}

enum FlipState {
	case ON;
	case OFF;
}

class PulseQueue {
	static private ?PulseQueue $_instance = null;

	private \SplQueue $_queue;
	private array $counts;

	private function __construct() {
		$this->reset();
	}

	static function getQueue() : PulseQueue {
		if(is_null(self::$_instance))
			self::$_instance = new PulseQueue();

		return self::$_instance;
	}

	public function enqueue(PulseSignal $signal) : void {
		$this->counts[$signal->pulse->name]++;
		$this->_queue->enqueue($signal);
	}

	public function dequeue() : ?PulseSignal {
		if($this->_queue->count())
			return $this->_queue->dequeue();

		return null;
	}
	
	public function reset() : void {
		$this->_queue = new \SplQueue();
		$this->counts = [Pulse::LOW->name => 0, Pulse::HIGH->name => 0];
	}

	public function getCounts() : array {
		return $this->counts;
	}
}

class PulseSignal {
	function __construct(
		public Pulse $pulse, public Module $from, public Module $to
	) {}
}

abstract class Module {
	public string $name;
	public array $schema = [];
	public array $inputs = [];
	public array $outputs = [];

	public function __construct(string $name='', array $schema=[]) {
		$this->name = $name;
		$this->schema = $schema;
	}
	
	public function reset() : void {}

	public function receive(PulseSignal $signal) : void {}

	protected function send(Pulse $pulse) : void {
		foreach($this->outputs as $output) {
			PulseQueue::getQueue()->enqueue(
				new PulseSignal($pulse, $this, $output)
			);
		}
	}

	public function addInput(Module $input) : void {
		$this->inputs[$input->name] = $input;
	}
	
	public function getInputs() : array {
		return $this->inputs;
	}

	public function addOutput(Module $output) : void {
		$this->outputs[$output->name] = $output;
	}
}

class MockModule extends Module {}

class Broadcaster extends Module {
	function receive(PulseSignal $signal) : void {
		$this->send($signal->pulse);
		parent::receive($signal);
	}
}

class FlipFlop extends Module {
	public FlipState $state = FlipState::OFF;

	function reset(): void {
		$this->state = FlipState::OFF;
	}
	
	function receive(PulseSignal $signal) : void {
		if($signal->pulse == Pulse::HIGH)
			return;

		if($this->state == FlipState::OFF) {
			$this->state = FlipState::ON;
			$this->send(Pulse::HIGH);
		} else {
			$this->state = FlipState::OFF;
			$this->send(Pulse::LOW);
		}
		
		parent::receive($signal);
	}
}

class Conjunction extends Module {
	private array $_memory = [];

	public function reset() : void {
		$this->_memory = array_fill_keys(array_keys($this->_memory), Pulse::LOW);
	}
	
	public function addInput(Module $input): void {
		// Default to low for each
		$this->_memory[$input->name] = Pulse::LOW;
		parent::addInput($input);
	}

	function receive(PulseSignal $signal) : void {
		// Remember pulse per input
		$this->_memory[$signal->from->name] = $signal->pulse;

		// after receive update memory, if all high send low, otherwise high
		if($this->areInputsAllHigh()) {
			$this->send(Pulse::LOW);
		} else {
			$this->send(Pulse::HIGH);
		}
		
		parent::receive($signal);
	}
	
	function areInputsAllHigh() : bool {
		return count($this->_memory) == count(array_filter(
			$this->_memory,
			fn(Pulse $p) => $p == Pulse::HIGH
		));
	}
}

class Button extends Module {
	function press() : void {
		// Send low pulse to the broadcast module
		$this->send(Pulse::LOW);
	}
}

$modules = [];

$lines = explode("\n", file_get_contents('../../data/2023/20/data.txt'));

// First read all the modules and index by name: %a -> b,c
foreach($lines as $line) {
	$matches = [];

	preg_match('/([%&]?)([a-z]+) -> ([a-z, ]+)/', $line, $matches);

	if ('&' == $matches[1]) {
		$module = new Conjunction($matches[2], $matches);
	} else if ('%' == $matches[1]) {
		$module = new FlipFlop($matches[2], $matches);
	} else if ('broadcaster' == $matches[2]) {
		$module = new Broadcaster($matches[2], $matches);
	} else {
		$module = new MockModule($matches[2], $matches);
	}

	$modules[$matches[2]] = $module;
}

// Link modules together
foreach($modules as $module) {
	foreach(explode(', ', $module->schema[3] ?? '') as $output) {
		// We can send output to an undefined module
		if(!array_key_exists($output, $modules))
			$modules[$output] = new MockModule($output);
		
		$module->addOutput($modules[$output]);
		$modules[$output]->addInput($module);
	}
}

// ==================================================
// Part 1: 1020211150

// Start a new queue for processing pulse events in breadth order
$queue = PulseQueue::getQueue();

// Create a button module and send its output to the broadcaster module
$button = new Button("button");
$button->addOutput($modules['broadcaster']);

// Press the button module 1,000 times
for($presses = 1; $presses <= 1_000; $presses++) {
	$button->press();
	
	// Process signal events in order until done before pressing the button again
	while($signal = $queue->dequeue()) {
		// Send the signal to the destination module
		$signal->to->receive($signal);
	}
}

echo "Part 1: ", array_product($queue->getCounts()), PHP_EOL;

// ==================================================
// Part 2: 238815727638557

// Reset the network and queue for a new run
array_walk($modules, fn(Module $module) => $module->reset());
$queue->reset();

// We're looking for cycles in hubs (conjunctions with more than 1 input)
// See: [images/AoC2023-Day20.png]
$watch_nodes = array_reduce(
	// Look at the parents of our rx module
	current($modules['rx']->getInputs())->getInputs(),
	function(array $results, Module $module) {
		// While our conjunction has only one parent, traverse to its parent
		while(1 == count($module->getInputs()))
			$module = current($module->getInputs());
		
		// Add the first multi-input conjunction parent to the watch list
		return array_unique(array_merge($results, [$module->name]));
	},
	initial: []
);

$cycles = [];

// We don't need to press the button that many times to find all of our cycles
for($presses = 1; $presses <= 10_000; $presses++) {
	$button->press();
	
	// Process the signal queue like in part 1
	while($signal = $queue->dequeue()) {
		// Send the signal to the destination module
		$signal->to->receive($signal);
		
		// When the signal pulse was high
		if($signal->pulse == Pulse::HIGH)
		foreach($watch_nodes as $node) {
			// Check all watch nodes to see if they're also high
			if ($signal->to == $modules[$node] && $modules[$node]->areInputsAllHigh()) {
				// If so, track their button press delta
				$cycles[$node] = $presses - ($cycles[$node] ?? 0);
				
				// If we have delta cycles for all watch nodes
				if(count($watch_nodes) == count($cycles)) {
					// Break and return the answer
					break 3;
				}
			}
		}
	}
}

// The first button press with all high signals on our watch nodes will be
// the lcm of their cycle deltas. nr=3847 gl=3851 gk=4003 hr=4027
echo "Part 2: ", Math::lcm($cycles), PHP_EOL;