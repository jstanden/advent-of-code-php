<?php
//$data = explode("\n", file_get_contents("test.txt"));
$data = explode("\n", file_get_contents("data.txt"));

enum BlockFace : int {
    case TOP = 1;
    case BOTTOM = 2;
    case LEFT = 4;
    case RIGHT = 8;
    case FRONT = 16;
    case BACK = 32;
}

class Block {
    public int $x;
    public int $y;
    public int $z;
    public int $facesVisible = 0;

    public function __construct($x, $y, $z) {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
    }

    public function name() : string {
        return sprintf('%d,%d,%d', $this->x, $this->y, $this->z);
    }

    public function setOpposingFaceVisible(BlockFace $face) : void {
        $this->facesVisible = $this->facesVisible | match($face) {
            BlockFace::BACK => BlockFace::FRONT->value,
            BlockFace::FRONT => BlockFace::BACK->value,
            BlockFace::LEFT => BlockFace::RIGHT->value,
            BlockFace::RIGHT => BlockFace::LEFT->value,
            BlockFace::TOP => BlockFace::BOTTOM->value,
            BlockFace::BOTTOM => BlockFace::TOP->value,
        };
    }
}

class World {
    public array $blocks = [];
    public array $air_visited = [];

    public array $x_extents = [PHP_INT_MAX, PHP_INT_MIN];
    public array $y_extents = [PHP_INT_MAX, PHP_INT_MIN];
    public array $z_extents = [PHP_INT_MAX, PHP_INT_MIN];

    public function setExtents(array $data) : void {
        foreach($data as $coords) {
            list($x,$y,$z) = explode(',', $coords);
            $this->x_extents = [min($this->x_extents[0],$x), max($this->x_extents[1],$x)];
            $this->y_extents = [min($this->y_extents[0],$y), max($this->y_extents[1],$y)];
            $this->z_extents = [min($this->z_extents[0],$z), max($this->z_extents[1],$z)];
        }
    }

    public function addBlock(int $x, int $y, int $z) : Block {
        $block = new Block($x, $y, $z);
        $this->blocks[implode(',',[$x,$y,$z])] = $block;

        foreach($this->isOnExtent($x, $y, $z) as $face) { /* @var $face BlockFace */
            //printf("Block %s starts on extent %s\n", $block->name(), $face->name);
            $block->facesVisible = $block->facesVisible | $face->value;
        }

        return $block;
    }

    public function getBlock(int $x, int $y, int $z) : ?Block {
        return $this->blocks[sprintf('%d,%d,%d', $x, $y, $z)] ?? null;
    }

    public function isOnExtent(int $x, int $y, int $z) : array {
        $results = [];

        if($x == $this->x_extents[0])
            $results[] = BlockFace::LEFT;
        if($x == $this->x_extents[1])
            $results[] = BlockFace::RIGHT;
        if($y == $this->y_extents[0])
            $results[] = BlockFace::BOTTOM;
        if($y == $this->y_extents[1])
            $results[] = BlockFace::TOP;
        if($z == $this->z_extents[0])
            $results[] = BlockFace::BACK;
        if($z == $this->z_extents[1])
            $results[] = BlockFace::FRONT;

        return $results;
    }

    public function getNeighbors(int $x, int $y, int $z) : array {
        return array_filter([
            BlockFace::LEFT->value => $this->getBlock($x-1, $y, $z),
            BlockFace::RIGHT->value => $this->getBlock($x+1, $y, $z),
            BlockFace::BOTTOM->value => $this->getBlock($x, $y-1, $z),
            BlockFace::TOP->value => $this->getBlock($x, $y+1, $z),
            BlockFace::BACK->value => $this->getBlock($x, $y, $z-1),
            BlockFace::FRONT->value => $this->getBlock($x, $y, $z+1),
        ], fn($candidate) => !is_null($candidate));
    }

    public function getNeighborPositions(int $x, int $y, int $z) : array {
        return array_filter([
            BlockFace::LEFT->value => $x-1 >= $this->x_extents[0] ? [$x-1, $y, $z] : null,
            BlockFace::RIGHT->value => $x+1 <= $this->x_extents[1] ? [$x+1, $y, $z] : null,
            BlockFace::BOTTOM->value => $y-1 >= $this->y_extents[0] ? [$x, $y-1, $z] : null,
            BlockFace::TOP->value => $y+1 <= $this->y_extents[1] ? [$x, $y+1, $z] : null,
            BlockFace::BACK->value => $z-1 >= $this->z_extents[0] ? [$x, $y, $z-1] : null,
            BlockFace::FRONT->value => $z+1 <= $this->z_extents[1] ? [$x, $y, $z+1] : null,
        ], fn($candidate) => !is_null($candidate));
    }

    // [TODO] This is inefficient
    public function isOutside(int $x, int $y, int $z) : bool {
        $is_outside = false;

        $cell_key = implode(',', [$x,$y,$z]);

        if(array_key_exists($cell_key, $this->air_visited))
            return true;

        $stack = new SplStack();
        $stack->push([$x,$y,$z]);
        $visited[] = true;

        while(!$stack->isEmpty()) {
            $current = $stack->pop();

            if($this->isOnExtent(...$current)) {
                $is_outside = true;
                break;
            }

            foreach($this->getNeighborPositions($current[0], $current[1], $current[2]) as $n) {
                if(
                    !array_key_exists(implode(',', $n), $visited)
                    && null == $this->getBlock(...$n)
                ) {
                    $stack->push($n);
                    $visited[implode(',', $n)] = true;
                }
            }
        }

        if($is_outside)
            $this->air_visited += $visited;

        return $is_outside;
    }

    // Part 1: Total surface area
    // Part 2: External surface area (not extent)
    function calculateSurfaceArea($only_external_faces=false) : int {
        for($x=$this->x_extents[0];$x<=$this->x_extents[1]+1;$x++) {
            for($y=$this->y_extents[0];$y<=$this->y_extents[1]+1;$y++) {
                for($z=$this->z_extents[0];$z<=$this->z_extents[1]+1;$z++) {
                    // If it's air
                    if(!$this->getBlock($x, $y, $z)) {
                        // Can we reach an extent from here?
                        if($only_external_faces && !$this->isOutside($x, $y, $z))
                            continue;

                        // If so, all of our neighbor faces are visible
                        foreach($this->getNeighbors($x, $y, $z) as $face => $block) {
                            /* @var $block Block */
                            //printf("Block %s is visible from %s\n", implode(',', [$block->x, $block->y, $block->z]), BlockFace::from($face)->name);
                            $block->setOpposingFaceVisible(BlockFace::from($face));
                        }
                    }
                }
            }
        }

        return array_reduce(
            array_map(fn($block) => decbin($block->facesVisible), $this->blocks),
            fn($carry, $binary) => $carry + count_chars($binary)[49],
            0
        );
    }
}

$world = new World();
$world->setExtents($data);

foreach($data as $coords) {
    list($x,$y,$z) = explode(',', $coords);
    $block = $world->addBlock($x,$y,$z);
}

echo "Part 2: ", $world->calculateSurfaceArea(true), PHP_EOL;
echo "Part 1: ", $world->calculateSurfaceArea(), PHP_EOL;
