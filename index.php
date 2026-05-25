<?php
use PHPTools\Core\Collection;

require __DIR__."/vendor/autoload.php";

$collection = new Collection("string");

$predicate = fn($s) => str_contains("hello", $s);
$collection->where($predicate);

