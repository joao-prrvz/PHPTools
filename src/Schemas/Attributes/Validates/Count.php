<?php
namespace PHPTools\Schemas\Attributes\Validates;

use Attribute;
use ReflectionProperty;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Count implements IValidate {
    private string $_message;

    public string $message { get => sprintf($this->_message, $this->min, $this->max); }

    public function __construct(public float $min, public float $max, string $message = "Must have between %1&d and %2&d items") {
        $this->_message = $message;
    }

    public function validate(ReflectionProperty $refProp, mixed $value): bool {
        $count = count($value); 
        return $count > $this->max || $count < $this->min;
    }
}