<?php
namespace PHPTools\ORM\Queries;

use Exception;

class UpdateQuery implements IQuery {
    public string $table;
    public array $columns;
    private array $_conditions;
    /** @var SQLCondition[] */
    public array $conditions {
        get => $this->_conditions;
        set {
            if (count($value) < 1)
                throw new Exception("An UpdateQuery needs at least one condition");
            $this->_conditions = $value;
        }
    }

    public function __construct(string $table, array $columns, array $conditions) {
        $this->table = $table;
        $this->columns = $columns;
        $this->conditions = $conditions;
    }

    public function clone(): UpdateQuery{
        $conditions = array_map(fn(SQLCondition $c) => $c->clone(), $this->conditions);
        return new UpdateQuery($this->table, $this->columns, $conditions);
    }

    public function __toString(): string {
        $columns = $this->formatColumns();
        $table = $this->table;
        $condition = $this->formatConditions();
        $sql = "UPDATE `$table` SET $columns $condition";
        return rtrim($sql);
    }

    private function formatColumns(): string {
        return "`".implode("` = ?, `", $this->columns)."` = ?";
    }

    private function formatConditions(): string {
        if (count($this->conditions) < 1)
            return "";
        $result = "WHERE ";
        foreach ($this->conditions as $index => $condition) {
            $result .= $condition->format($index > 0). " ";
        }
        return rtrim($result);
    }
}