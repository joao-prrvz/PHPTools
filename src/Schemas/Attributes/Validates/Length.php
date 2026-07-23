<?php
namespace PHPTools\Schemas\Attributes\Validates;

use Attribute;
use ReflectionProperty;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Length implements IValidate {
    private string $_message;

    public string $message { get => sprintf($this->_message, $this->min, $this->max); }

    public function __construct(public int $min, public int $max, string $message = "Must be between %1\$d and %2\$d characters") {
        $this->_message = $message;
    }

    public function validate(ReflectionProperty $refProp, mixed $value): bool {
        $length = strlen($value);
        return $length <= $this->max && $length >= $this->min;
    }
}