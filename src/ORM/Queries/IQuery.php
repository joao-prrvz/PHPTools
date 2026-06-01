<?php
namespace PHPTools\ORM\Queries;

interface IQuery {
    public string $table { get; set; }

    public function __toString(): string;
}