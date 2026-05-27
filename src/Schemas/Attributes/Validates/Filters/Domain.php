<?php
namespace PHPTools\Schemas\Attributes\Validates\Filters;

use Attribute;
use ReflectionProperty;
use Override;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Domain extends Filter {
    public function __construct(private bool $flagHostname = true, string $message = "Is not a valide domain") {
        parent::__construct(FILTER_VALIDATE_DOMAIN, $message);
    }

    #[Override]
    public function validate(ReflectionProperty $refProp, mixed $value): bool {
        return filter_var($value, $this->filter, $this->flagHostname ? FILTER_FLAG_HOSTNAME : 0) !== false;
    }
}