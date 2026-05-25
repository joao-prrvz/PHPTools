<?php
namespace PHPTools\ORM\Parsers;

interface IParser {
    
    public function parse(callable $predicate);
}