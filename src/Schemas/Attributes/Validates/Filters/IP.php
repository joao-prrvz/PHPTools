<?php
namespace PHPTools\Schemas\Attributes\Validates\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class IP extends Filter {
    public function __construct(string $message = "Is not a valide ip address") {
        parent::__construct(FILTER_VALIDATE_IP, $message);
    }
}