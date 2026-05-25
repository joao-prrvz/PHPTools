<?php
namespace PHPTools\Tests\Core;

use PHPTools\Core\Sort;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SortTest extends TestCase {

    #[Test]
    public function int_merge() {
        $array = [10, 9, 8, 7, 6, 5, 4, 3, 2, 1];
        $array = Sort::merge($array);
        $result = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $this->assertSame($result, $array);
    }

    #[Test]
    public function string_merge() {
        $array = ["e", "b", "a", "d", "c", "g", "f"];
        $array = Sort::merge($array);
        $result = ["a", "b", "c", "d", "e", "f", "g"];
        $this->assertSame($result, $array);
    }
}