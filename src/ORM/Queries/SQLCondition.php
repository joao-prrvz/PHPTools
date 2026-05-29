<?php
namespace PHPTools\ORM\Queries;

class SQLCondition {
    public string $expression;
    public SQLOperator $operator;

    public function __construct(string $expression, SQLOperator $operator = SQLOperator::And) {
        $this->expression = $expression;
        $this->operator = $operator;
    }

    public function format(bool $displayOperator) {
        $operator = $displayOperator ? $this->operator->value : "";
        return ltrim("$operator $this->expression");
    }
}