<?php
namespace PHPTools\ORM\Queries;

class SelectQuery {
    public string $table;
    public array $columns = [];
    /** @var SQLCondition[] */
    public array $conditions = [];
    public ?int $limit = null; 
    public int $offset = 0; 

    public function __construct(string $table) {
        $this->table = $table;
    }

    public function __toString() {
        $columns = $this->formatColumns();
        $table = $this->table;
        $limitAndOffset = $this->formatLimitAndOffset();
        $condition = $this->formatConditions();
        $sql = "SELECT $columns FROM `$table` $condition $limitAndOffset";
        return rtrim($sql);
    }

    private function formatColumns(): string {
        $table = $this->table;
        return "`$table`.`".implode("`, `$table`.`", $this->columns)."`";
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

    private function formatLimitAndOffset(): string {
        if ($this->limit === null)
            return "";
        $limit = $this->limit;
        $offset = $this->offset;
        return "LIMIT $limit OFFSET $offset";
    }
}