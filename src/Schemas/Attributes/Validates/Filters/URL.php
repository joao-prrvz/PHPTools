<?php
namespace PHPTools\Schemas\Attributes\Validates\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class URL extends Filter {
    public function __construct(string $message = "Is not a valide url") {
        parent::__construct(FILTER_VALIDATE_URL, $message);
    }
}