<?php
namespace PHPTools\Schemas\Attributes\Validates;

use Attribute;
use ReflectionProperty;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Range implements IValidate {
    private string $_message;

    public string $message { get => sprintf($this->_message, $this->min, $this->max); }

    public function __construct(public float $min, public float $max, string $message = "Must be between %1\$d and %2\$d") {
        $this->_message = $message;
    }

    public function validate(ReflectionProperty $refProp, mixed $value): bool {
        return $value <= $this->max && $value >= $this->min;
    }
}