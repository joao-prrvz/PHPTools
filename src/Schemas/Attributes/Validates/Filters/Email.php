<?php
namespace PHPTools\Schemas\Attributes\Validates\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Email extends Filter {
    public function __construct(string $message = "Is not a valide email") {
        parent::__construct(FILTER_VALIDATE_EMAIL, $message);
    }
}