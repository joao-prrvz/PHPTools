<?php
namespace PHPTools\Schemas\Attributes\Validates;

use Attribute;
use PHPTools\Schemas\Traits\DefaultMessage;
use ReflectionProperty;

#[Attribute(Attribute::TARGET_PROPERTY)]
class LengthMin implements IValidate {
    public string $message;

    use DefaultMessage;

    public function __construct(private int $value, ?string $message = null) {
        $this->message = $this->default($message, "Must have more or equal to $value characters");
    }

    public function validate(ReflectionProperty $refProp, mixed $value): bool {
        if (get_debug_type($value) !== "string") {
            trigger_error("LengthMin is supposed to be used with a string");
            return false;
        }
        return strlen($value) >= $this->value;
    }
}