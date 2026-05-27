<?php
namespace PHPTools\Schemas\Attributes\Validates;

use Attribute;
use PHPTools\Schemas\Traits\DefaultMessage;
use ReflectionProperty;

#[Attribute(Attribute::TARGET_PROPERTY)]
class CountMin implements IValidate {
    public string $message;

    use DefaultMessage;

    public function __construct(private int $value, ?string $message = null) {
        $this->message = $this->default($message, "Must have more or equal to $value items");
    }

    public function validate(ReflectionProperty $refProp, mixed $value): bool {
        if (get_debug_type($value) !== "array") {
            trigger_error("CountMin is supposed to be used with an array");
            return false;
        }
        return count($value) >= $this->value;
    }
}