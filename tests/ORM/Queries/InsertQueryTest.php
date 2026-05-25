<?php
namespace PHPTools\Tests\ORM\Queries;

use Exception;
use PHPTools\ORM\Queries\InsertQuery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class InsertQueryTest extends TestCase {

    #[Test]
    public function to_string() {
        $insertQuery = new InsertQuery("User", ["id", "email"], 1);
        $this->assertEquals("INSERT INTO `User` (`id`, `email`) VALUES (?, ?)", "$insertQuery");
    }

    #[Test]
    public function to_string_with_multiple_count() {
        $insertQuery = new InsertQuery("User", ["id", "email"], 3);
        $this->assertEquals("INSERT INTO `User` (`id`, `email`) VALUES (?, ?), (?, ?), (?, ?)", "$insertQuery");
    }

    #[Test]
    public function to_string_with_less_than_0_count() {
        $this->expectException(Exception::class);
        new InsertQuery("User", ["id", "email"], -1);
    }
}