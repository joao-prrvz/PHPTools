<?php
namespace PHPTools\ORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Block {
    const int INSERT = 0;
    const int UPDATE = 1;
    const int ALL = 2;
    public array $values;

    public function __construct(int $value = Block::ALL, int ...$values) {
        $this->values = array_merge([$value], $values);
    }
}