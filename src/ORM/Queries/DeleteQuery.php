<?php
namespace PHPTools\ORM\Queries;

use Exception;

class DeleteQuery implements IQuery {
    public string $table;
    private array $_conditions;
    /** @var SQLCondition[] */
    public array $conditions {
        get => $this->_conditions;
        set {
            if (count($value) < 1)
                throw new Exception("A DeleteQuery needs at least one condition");
            $this->_conditions = $value;
        }
    }

    public function __construct(string $table, array $conditions) {
        $this->table = $table;
        $this->conditions = $conditions;
    }

    public function __clone() {
        $this->conditions = array_map(fn(SQLCondition $c) => clone $c, $this->conditions);
    }

    public function __toString(): string {
        $table = $this->table;
        $condition = $this->formatConditions();
        $sql = "DELETE FROM `$table` $condition";
        return rtrim($sql);
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