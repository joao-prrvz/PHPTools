<?php
namespace PHPTools\Schemas\Attributes\Validates;

use Attribute;
use Countable;
use ReflectionProperty;
use Override;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Max implements IValidate{
    public string $message;

    public function __construct(private float $value, ?string $message = null){
        if ($message === null)
            $message = "Must be smaller or equal to $value";
        $this->message = $message;
    }

    public function validate(ReflectionProperty $refProp, mixed $value): bool {
        switch(get_debug_type($value)) {
            case "int":
            case "float":
                return $value <= $this->value;
            case "array":
                return count($value) <= $this->value;
            case "string":
                return strlen($value) <= $this->value;
            default:
                if ($value instanceof Countable)
                    return count($value) <= $this->value;
                return false;
        }
    }
}