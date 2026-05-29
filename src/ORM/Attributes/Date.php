<?php
namespace PHPTools\ORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Date {
    public function __construct(public string $format)
    { }
}