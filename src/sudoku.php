<?php

class Sudoku
{
    protected $grid;

    public function __construct()
    {
        $this->grid = array_fill(0, 81, null);
    }

    public function getGrid()
    {
        return $this->grid;
    }

    public function setGrid($grid = null)
    {
        if($grid == null) {
            $this->grid = array_fill(0, 81, null);
        } else {
            $this->grid = $grid;   
        }
    }
}
