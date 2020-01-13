<?php
class Puzzle
{
    protected $puzzle = [];
    protected $puzzleColumns = [];
    protected $puzzleBoxes = [];

    protected $solution = [];
    protected $solutionColumns = [];
    protected $solutionBoxes = [];

    protected $cellSize = 3;

    protected $boxLookup;

    public function __construct($cellSize = 3, array $puzzle = [], array $solution = [])
    {
        $this->setCellSize($cellSize, $puzzle, $solution);
    }

    public function getCellSize()
    {
        return $this->cellSize;
    }

    public function setCellSize($cellSize, array $puzzle = [], array $solution = [])
    {
        if (is_integer($cellSize) && $cellSize > 1) {
            $this->cellSize = $cellSize;
            $this->setPuzzle($puzzle);
            $this->setSolution($solution);
            return true;
        }
        return false;
    }

    public function getGridSize()
    {
        return $this->cellSize * $this->cellSize;
    }

    public function getPuzzle()
    {
        return $this->puzzle;
    }

    public function setPuzzle(array $puzzle = [])
    {
        if ($this->isValidPuzzleFormat($puzzle)) {
            $this->puzzle = $puzzle;
            $this->setSolution($this->puzzle);
            $this->prepareReferences();
            return true;
        } else {
            $this->puzzle = $this->generateEmptyPuzzle();
            $this->setSolution($this->puzzle);
            $this->prepareReferences();
            return false;
        }
    }

    public function getSolution()
    {
        return $this->solution;
    }

    public function setSolution(array $solution)
    {
        if ($this->isValidPuzzleFormat($solution)) {
            $this->solution = $solution;
            $this->prepareReferences(false);
            return true;
        } else {
            return false;
        }
    }

    public function solve()
    {
        if ($this->isSolvable()) {
            if ($this->calculateSolution($this->solution)) {
                return true;
            }
        }
        return false;
    }

    public function isSolved()
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

    public function isSolvable()
    {
        return $this->checkConstraints($this->puzzle, $this->puzzleColumns, $this->puzzleBoxes, true);
    }

    public function generatePuzzle($cellCount = 15)
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

    protected function checkConstraints($rows, $columns, $boxes, $allowZeros = false)
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

    protected function generateEmptyPuzzle()
    {
        return array_fill(0, $this->getGridSize(), array_fill(0, $this->getGridSize(), 0));
    }

    protected function isValidPuzzleFormat(array $puzzle)
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

    protected function getValidOptions($rowIndex, $columnIndex)
    {
        $invalid = array_merge($this->solution[$rowIndex], $this->solutionColumns[$columnIndex], $this->solutionBoxes[$this->boxLookup[$rowIndex][$columnIndex]]);
        $invalid = array_flip(array_flip($invalid));
        $valid = array_diff(range(1, $this->getGridSize()), $invalid);
        shuffle($valid);
        return $valid;
    }

    protected function checkContainerForViolations(array $container, $allowZeros = false)
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
    
    protected function getCellCount()
    {
        return ($this->getGridSize() * $this->getGridSize());
    }
    
    protected function prepareReferences($puzzle = true)
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

    protected function setColumns(array &$source, array &$columns)
    {
        $columns = [];
        for ($i = 0; $i < $this->getGridSize(); $i++) {
            for ($j = 0; $j < $this->getGridSize(); $j++) {
                $columns[$j][$i] = &$source[$i][$j];
            }
        }
    }

    protected function setBoxes(array &$source, array &$boxes)
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
        if (in_array($x, array(0, 3, 6))) {
            echo ("║");
        } else if (in_array($x, array(1, 2, 4, 5, 7, 8))) {
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
    if (in_array($i, array(0, 1, 3, 4, 6, 7))) {
        echo ("<br>╟───┼───┼───╢───┼───┼───╢───┼───┼───╢<br>");
    } else if (in_array($i, array(2, 5))) {
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