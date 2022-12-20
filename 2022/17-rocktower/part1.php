<?php
// Jeff Standen <https://phpc.social/@jeff>
// (x,y) origin is lower left

const FRAME_RATE_MS = 100_000;

enum Direction : string {
    case DOWN = 'v';
    case LEFT = '<';
    case RIGHT = '>';
}

class Vector {
    public int $x;
    public int $y;

    public function __construct(int $x, int $y) {
        $this->x = $x;
        $this->y = $y;
    }
}

enum RockType : int {
    case MINUS = 0;
    case PLUS = 1;
    case J = 2;
    case I = 3;
    case SQUARE = 4;
}

enum RockState {
    case MOVING;
    case STOPPED;
}

class Rock {
    public RockType $type;
    public RockState $state;
    public int $x;
    public int $y;

    public function __construct(RockType $type, int $x, int $y, RockState $state=RockState::MOVING) {
        $this->type = $type;
        $this->state = $state;
        $this->x = $x;
        $this->y = $y;
    }

    public function width() : int {
        $sprite = $this->sprite();
        return count($sprite[0]);
    }

    public function height() : int {
        $sprite = $this->sprite();
        return count($sprite);
    }

    public function sprite() : array {
        return match($this->type) {
            RockType::I => [[1],[1],[1],[1]],
            RockType::J => [[1,1,1],[0,0,1],[0,0,1]],
            RockType::MINUS => [[1,1,1,1]],
            RockType::PLUS => [[0,1,0],[1,1,1],[0,1,0]],
            RockType::SQUARE => [[1,1],[1,1]],
        };
    }
}

enum BlitOperation {
    case OVERWRITE;
    case MASK;
    case AND;
    case OR;
    case XOR;
};

class Chamber {
    const WIDTH = 7;

    private array $_grid = [];
    private int $_rocksDropped = 0;

    public function nextRock() : Rock {
        $rockLineHeight = $this->getRockLineHeight();

        $rockTypes = RockType::cases();
        $rockType = $rockTypes[$this->_rocksDropped++ % count($rockTypes)];

        $rock = new Rock($rockType, 2, $rockLineHeight + 3);

        // Add lines to the grid for this rock
        for($y = array_key_last($this->_grid) ?? 0; $y < $rockLineHeight + 3 + $rock->height(); $y++) {
            if(!array_key_exists($y, $this->_grid))
                $this->_grid[$y] = [0, 0, 0, 0, 0, 0, 0];
        }

        $sprite = $rock->sprite();
        $this->_bitBlit($sprite, $this->_grid, 0, 0, $rock->x, $rock->y, $rock->width(), $rock->height());
        return $rock;
    }

    private function _drawRock(Rock $rock) : void {
        $sprite = $rock->sprite();
        $this->_bitBlit($sprite, $this->_grid, 0, 0, $rock->x, $rock->y, $rock->width(), $rock->height(), BlitOperation::OR);
    }

    private function _hideRock(Rock $rock) : void {
        $sprite = $rock->sprite();
        $this->_bitBlit($sprite, $this->_grid, 0, 0, $rock->x, $rock->y, $rock->width(), $rock->height(), BlitOperation::MASK);
    }

    private function _rockIntersects(Rock $rock, Vector $vector) : bool {
        $seek = $rock->sprite();
        $this->_bitBlit($this->_grid, $seek, $rock->x + $vector->x, $rock->y + $vector->y, 0, 0, $rock->width(), $rock->height(), BlitOperation::AND);

        // We collided
        if(array_sum(array_merge(...$seek)) > 0)
            return true;

        // All clear
        return false;
    }

    public function move(Rock $rock, Direction $d) : bool {
        $this->_hideRock($rock);

        switch($d) {
            case Direction::DOWN:
                $vector = new Vector(0, -1);

                if($rock->y + $vector->y < 0) {
                    // Draw us back where we were
                    $this->_drawRock($rock);
                    return false;
                }

                // Check for intersections where we want to be
                if($this->_rockIntersects($rock, $vector)) {
                    $this->_drawRock($rock);
                    return false;
                }

                // Otherwise move us
                $rock->x += $vector->x;
                $rock->y += $vector->y;
                $this->_drawRock($rock);
                break;

            case Direction::LEFT:
                $vector = new Vector(-1, 0);

                if($rock->x + $vector->x < 0) {
                    // Draw us back where we were
                    $this->_drawRock($rock);
                    return false;
                }

                // Check for intersections where we want to be
                if($this->_rockIntersects($rock, $vector)) {
                    $this->_drawRock($rock);
                    return false;
                }

                // Otherwise move us
                $rock->x += $vector->x;
                $rock->y += $vector->y;
                $this->_drawRock($rock);
                break;

            case Direction::RIGHT:
                $vector = new Vector(1, 0);

                if($rock->x + $rock->width()-1 + $vector->x > 6) {
                    // Draw us back where we were
                    $this->_drawRock($rock);
                    return false;
                }

                // Check for intersections where we want to be
                if($this->_rockIntersects($rock, $vector)) {
                    $this->_drawRock($rock);
                    return false;
                }

                // Otherwise move us
                $rock->x += $vector->x;
                $rock->y += $vector->y;
                $this->_drawRock($rock);
                break;
        }

        return true;
    }

    private function _bitBlit(
        array $src, array &$dst,
        int $srcX, int $srcY,
        int $dstX, int $dstY,
        int $width, int $height,
        BlitOperation $operation = BlitOperation::OR) : void {

        // Update X/Y
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $dst[$dstY + $y][$dstX + $x] = match($operation) {
                    BlitOperation::OVERWRITE => $src[$srcY + $y][$srcX + $x],
                    BlitOperation::MASK => $src[$srcY + $y][$srcX + $x] ? 0 : $dst[$dstY + $y][$dstX + $x],
                    BlitOperation::AND => ($src[$srcY + $y][$srcX + $x] && $dst[$dstY + $y][$dstX + $x]) ? 1 : 0,
                    BlitOperation::OR => ($src[$srcY + $y][$srcX + $x] || $dst[$dstY + $y][$dstX + $x]) ? 1 : 0,
                    BlitOperation::XOR => ($src[$srcY + $y][$srcX + $x] xor $dst[$dstY + $y][$dstX + $x]) ? 1 : 0,
                };
            }
        }
    }

    public function renderFrame(int $delay=FRAME_RATE_MS) : void {
        echo "\e[H\e[J"; // cls

        if(empty($this->_grid))
            return;

        for ($y = count($this->_grid)-1; $y >= max(0, count($this->_grid)-25); $y--) {
            for ($x = 0; $x < self::WIDTH; $x++) {
                echo !($this->_grid[$y][$x] ?? null) ? '.' : '#';
            }
            echo PHP_EOL;
        }

        echo "Rock: ", $this->_rocksDropped;
        echo " Top: ", $this->getRockLineHeight();
        echo PHP_EOL;

        usleep($delay);
    }

    public function getRockLineHeight() : int {
        $y = 0;
        while(array_sum($this->_grid[$y] ?? [])) $y++;
        return $y;
    }
		
		public function getChamberRow(int $y) : array {
			return $this->_grid[$y];
		}

    public function getRockCount() : int {
        return $this->_rocksDropped;
    }
}

$chamber = new Chamber();

$move_counter = 0;
//$move_sequence = file_get_contents("test.txt");
$move_sequence = file_get_contents("data.txt");
$move_sequence_len = strlen($move_sequence);

$rock = $chamber->nextRock();

$until_rock = 2_022;

$chamber->renderFrame();
//$last_move = 0;
//$last_height = 0;

while(true) {
    $d = $move_sequence[$move_counter % $move_sequence_len];

    $chamber->move($rock, Direction::from($d));

    $chamber->renderFrame();

    if(!$chamber->move($rock, Direction::DOWN)) {
			
			$tower_height = $chamber->getRockLineHeight();
			$top_row = $chamber->getChamberRow($tower_height-1);
			
//			if($rock->type == RockType::SQUARE) {
//				printf("Rock: %d Shape: %s Height: %d (%d) Moves: %d (%d)\n", $chamber->getRockCount(), $rock->type->name, $tower_height, $tower_height-$last_height, $move_counter, $move_counter-$last_move);
//				$last_move = $move_counter;
//				$last_height = $tower_height;
//			}

			if($chamber->getRockCount() == $until_rock) {
					echo "Part 1: ", $tower_height, PHP_EOL;
					break;
			}

			$rock = $chamber->nextRock();
    }

    $chamber->renderFrame();
    $move_counter++;
}

$chamber->renderFrame();