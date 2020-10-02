<?php
class Puzzle
{
    protected array $puzzle = [];
    protected array $puzzleColumns = [];
    protected array $puzzleBoxes = [];

    protected array $solution = [];
    protected array $solutionColumns = [];
    protected array $solutionBoxes = [];

    protected int $cellSize = 3;

    protected $boxLookup;

    public function __construct(int $cellSize = 3, array $puzzle = [], array $solution = [])
    {
        $this->setCellSize($cellSize, $puzzle, $solution);
    }

    public function getCellSize(): int
    {
        return $this->cellSize;
    }

    public function setCellSize(int $cellSize, array $puzzle = [], array $solution = []): bool
    {
        if (is_integer($cellSize) && $cellSize > 1) {
            $this->cellSize = $cellSize;
            $this->setPuzzle($puzzle);
            $this->setSolution($solution);
            return true;
        }
        return false;
    }

    public function getGridSize(): int
    {
        return $this->cellSize * $this->cellSize;
    }

    public function getPuzzle(): array
    {
        return $this->puzzle;
    }

    public function setPuzzle(array $puzzle = []): bool
    {
        if ($this->isValidPuzzleFormat($puzzle)) {
            $this->puzzle = $puzzle;
            $this->setSolution($this->puzzle);
            $this->prepareReferences();
            return true;
        }
        
        $this->puzzle = $this->generateEmptyPuzzle();
        $this->setSolution($this->puzzle);
        $this->prepareReferences();
        return false;
    }

    public function getSolution(): array
    {
        return $this->solution;
    }

    public function setSolution(array $solution): bool
    {
        if ($this->isValidPuzzleFormat($solution)) {
            $this->solution = $solution;
            $this->prepareReferences(false);
            return true;
        }
        
        return false;
    }

    public function solve(): bool
    {
        return $this->isSolvable() && $this->calculateSolution($this->solution);
    }

    public function isSolved(): bool
    {
        if (!$this->checkConstraints($this->solution, $this->solutionColumns, $this->solutionBoxes)) {
            return false;
        }
        foreach ($this->puzzle as $rowIndex => $row) {
            foreach ($row as $columnIndex => $column) {
                if ($column !== 0) {
                    if ($this->puzzle[$rowIndex][$columnIndex] != $this->solution[$rowIndex][$columnIndex]) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    public function isSolvable(): bool
    {
        return $this->checkConstraints($this->puzzle, $this->puzzleColumns, $this->puzzleBoxes, true);
    }

    public function generatePuzzle(int $cellCount = 15): bool
    {
        if (!is_integer($cellCount) || $cellCount < 0 || $cellCount > $this->getCellCount()) {
            return false;
        }
        $this->setPuzzle($this->generateEmptyPuzzle());
        if ($cellCount > 0) {
            $this->solve();
            $cells = array_rand(range(0, ($this->getCellCount() - 1)), $cellCount);
            $i = 0;
            if (is_integer($cells)) {
                $cells = [$cells];
            }
            foreach ($this->solution as &$row) {
                foreach ($row as &$cell) {
                    if (!in_array($i++, $cells)) {
                        $cell = 0;
                    }
                }
            }

            $this->puzzle = unserialize(serialize($this->solution));
        }
        $this->prepareReferences();
        return true;
    }

    protected function checkConstraints(array $rows, array $columns, array $boxes, bool $allowZeros = false): bool
    {
        foreach ($rows as $rowIndex => $row) {
            if (!$this->checkContainerForViolations($row, $allowZeros)) {
                return false;
            }
            foreach ($columns as $columnIndex => $column) {
                if (!$this->checkContainerForViolations($column, $allowZeros)) {
                    return false;
                }
                if (!$this->checkContainerForViolations($boxes[$this->boxLookup[$rowIndex][$columnIndex]], $allowZeros)) {
                    return false;
                }
            }
        }
        return true;
    }

    protected function generateEmptyPuzzle(): array
    {
        return array_fill(0, $this->getGridSize(), array_fill(0, $this->getGridSize(), 0));
    }

    protected function isValidPuzzleFormat(array $puzzle): array
    {
        if (!is_array($puzzle) || count($puzzle) != $this->getGridSize()) {
            return false;
        }
        foreach ($puzzle as $row) {
            if (count($row) != $this->getGridSize()) {
                return false;
            }
        }
        return true;
    }

    protected function calculateSolution(array $puzzle)
    {
        $continue = true;
        while ($continue) {
            $options = null;
            foreach ($puzzle as $rowIndex => $row) {
                $columnIndex = array_search(0, $row);
                if ($columnIndex === false) {
                    continue;
                }
                $validOptions = $this->getValidOptions($rowIndex, $columnIndex);
                if (count($validOptions) == 0) {
                    return false;
                }
                break;
            }
            if (!isset($validOptions) || empty($validOptions)) {
                return $puzzle;
            }
            foreach ($validOptions as $key => $value) {
                $puzzle[$rowIndex][$columnIndex] = $value;
                $result = $this->calculateSolution($puzzle);
                if ($result == true) {
                    return $result;
                } else {
                    $puzzle[$rowIndex][$columnIndex] = 0;
                }
            }
            $continue = false;
        }
        return false;
    }

    protected function getValidOptions(int $rowIndex, int $columnIndex): array
    {
        $invalid = array_merge($this->solution[$rowIndex], $this->solutionColumns[$columnIndex], $this->solutionBoxes[$this->boxLookup[$rowIndex][$columnIndex]]);
        $invalid = array_flip(array_flip($invalid));
        $valid = array_diff(range(1, $this->getGridSize()), $invalid);
        shuffle($valid);
        return $valid;
    }

    protected function checkContainerForViolations(array $container, bool $allowZeros = false): bool
    {
        if (!$allowZeros && in_array(0, $container)) {
            return false;
        }
        if (($keys = array_keys($container, 0)) !== false) {
            foreach ($keys as $key) {
                unset($container[$key]);
            }
        }
        $flippedContainer = array_flip($container);
        $uniqueContainer = array_flip($flippedContainer);
        if (count($container) != count($uniqueContainer)) {
            return false;
        }
        foreach (range(1, $this->getGridSize()) as $index) {
            unset($flippedContainer[$index]);
        }
        if (!empty($flippedContainer)) {
            return false;
        }
        return true;
    }
    
    protected function getCellCount(): int
    {
        return ($this->getGridSize() * $this->getGridSize());
    }
    
    protected function prepareReferences(bool $puzzle = true): void
    {
        if ($puzzle) {
            $source = &$this->puzzle;
            $columns = &$this->puzzleColumns;
            $boxes = &$this->puzzleBoxes;
        } else {
            $source = &$this->solution;
            $columns = &$this->solutionColumns;
            $boxes = &$this->solutionBoxes;
        }
        $this->setColumns($source, $columns);
        $this->setBoxes($source, $boxes);
    }

    protected function setColumns(array &$source, array &$columns): void
    {
        $columns = [];
        for ($i = 0; $i < $this->getGridSize(); $i++) {
            for ($j = 0; $j < $this->getGridSize(); $j++) {
                $columns[$j][$i] = &$source[$i][$j];
            }
        }
    }

    protected function setBoxes(array &$source, array &$boxes): void
    {
        $boxes = [];
        for ($i = 0; $i < $this->getGridSize(); $i++) {
            for ($j = 0; $j < $this->getGridSize(); $j++) {
                $row = floor(($i) / $this->cellSize);
                $column =  floor(($j) / $this->cellSize);
                $box = (int) floor($row * $this->cellSize + $column);
                $cell = ($i % $this->cellSize) * ($this->cellSize) + ($j % $this->cellSize);
                $boxes[$box][$cell] = &$source[$i][$j];
                $this->boxLookup[$i][$j] = $box;
            }
        }
    }
}

$puzzle = new Puzzle();
$puzzle->generatePuzzle();
$array = $puzzle->getPuzzle();

echo ("╔═══╤═══╤═══╦═══╤═══╤═══╦═══╤═══╤═══╗<br>");
for ($i = 0; $i < 9; $i++) {
    for ($x = 0; $x < 9; $x++) {
        if ($x % 3 === 0) {
            echo ("║");
        } else {
            echo ("│");
        }
        $number = $array[$i][$x];
        if($number == 0) {
            echo (" _ ");
        } else {
            echo (" " . $number . " ");
        }
        if($x == 8) {
            echo ("║");
        }
    }
    if (in_array($i, [0, 1, 3, 4, 6, 7])) {
        echo ("<br>╟───┼───┼───╢───┼───┼───╢───┼───┼───╢<br>");
    } else if (in_array($i, [2, 5])) {
        echo ("<br>╠═══╪═══╪═══╬═══╪═══╪═══╬═══╪═══╪═══╣<br>");
    } else {
        echo ("<br>");
    }
}
echo ("╚═══╧═══╧═══╩═══╧═══╧═══╩═══╧═══╧═══╝<br>");
?>

<style>
    body {
        font-family: consolas;
    }
</style>
