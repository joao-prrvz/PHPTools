<?php
namespace PHPTools\ORM\Queries;

use Exception;

class InsertQuery implements IQuery {
    public string $table;
    public array $columns;
    private int $_count;
    public int $count {
        get => $this->_count;
        set {
            if ($value < 1)
                throw new Exception("Count must be bigger than 0");
            $this->_count = $value;
        }
    }

    public function __construct(string $table, array $columns, int $count) {
        $this->table = $table;
        $this->columns = $columns;
        $this->count = $count;
    }

    

    public function __toString(): string {
        $columns = $this->formatColumns();
        $table = $this->table;
        $values = $this->formatValues();
        $sql = "INSERT INTO `$table` ($columns) VALUES $values";
        return rtrim($sql);
    }

    private function formatColumns(): string {
        $table = $this->table;
        return "`".implode("`, `", $this->columns)."`";
    }

    private function formatValues(): string {
        $value = implode(", ", array_fill(0, count($this->columns), "?"));
        return implode(", ", array_fill(0, $this->count, "($value)"));
    }
}