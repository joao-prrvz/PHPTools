<?php
namespace PHPTools\Schemas\Attributes\Validates;

use Attribute;
use DateTime;
use PHPTools\Schemas\Traits\DefaultMessage;
use ReflectionProperty;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Date implements IValidate {
    public string $message;

    use DefaultMessage; 

    public function __construct(public string $format, ?string $message = null) {
        $this->message = $this->default($message, "The time given must be of the format $format");
    }

    public function validate(ReflectionProperty $refProp, mixed $value): bool {
        return ($value instanceof DateTime) || DateTime::createFromFormat($this->format, $value) !== false;
    }
}