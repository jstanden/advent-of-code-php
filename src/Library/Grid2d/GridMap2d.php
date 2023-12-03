<?php // @jeff@phpc.social
/** @noinspection PhpUnused */
/** @noinspection DuplicatedCode */

declare(strict_types=1);
namespace jstanden\AoC\Library\Grid2d;

class GridMap2d
{
    public array $grid = [];
    public array $extents = [];

    public function __construct(array $data)
    {
        $this->_loadData($data);
    }

    private function _loadData(array $data): void
    {
        // Store the grid extents
        $this->extents = [
            'x0' => 0,
            'x1' => array_reduce($data, fn($carry, $row) => max($carry, strlen($row) - 1), 0),
            'y0' => 0,
            'y1' => count($data) - 1,
        ];

        // Convert row strings to character arrays and pad all to the widest
        $data = array_map(fn($row) => str_split($row), $data);

        // Flip to an X,Y grid
        $this->grid = array_combine(
            array_keys($data[0]),
            array_values(array_map(fn($col) => array_column($data, $col), array_keys($data[0])))
        );
    }

    function euclideanDistance(Vector2d $a, Vector2d $b): float
    {
        return sqrt(
            pow($b->x - $a->x, 2)
            + pow($b->y - $a->y, 2)
        );
    }

    function getTile(Vector2d $loc): ?string
    {
        return $this->grid[$loc->x][$loc->y] ?? null;
    }

    function getRow(int $y): array
    {
        return array_column($this->grid, $y) ?? [];
    }

    function getRows(): array
    {
        return array_map(fn($y) => $this->getRow($y), range(0, $this->extents['y1']));
    }

    function getColumn(int $x): array
    {
        return $this->grid[$x] ?? [];
    }

    function getColumns(): array
    {
        return array_map(fn($x) => $this->getColumn($x), range(0, $this->extents['x1']));
    }

    public function getAdjacentNeighbors(Vector2d $vector) : array
    {
        return array_map(
            fn($direction) => (Vector2d::add($vector, $direction->getVector())),
            Vector2dDirection::cases()
        );
    }
}