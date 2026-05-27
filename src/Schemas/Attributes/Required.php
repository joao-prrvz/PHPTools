<?php
namespace PHPTools\Schemas\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Required {
    public function __construct(public string $message = "Is required")
    { }
}