<?php
namespace PHPTools\Schemas\Attributes\Validates\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Domain extends Filter {
    public function __construct(string $message = "Is not a valide domain") {
        parent::__construct(FILTER_VALIDATE_DOMAIN, $message);
    }
}