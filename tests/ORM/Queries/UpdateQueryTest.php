<?php
namespace PHPTools\Tests\ORM\Queries;

use PHPTools\ORM\Queries\UpdateQuery;
use PHPTools\ORM\Queries\SQLCondition;
use PHPTools\ORM\Queries\SQLOperator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UpdateQueryTest extends TestCase {

    #[Test]
    public function to_string() {
        $conditions = [
            new SQLCondition("`User`.`id` = ?")
        ];
        $updateQuery = new UpdateQuery("User", ["id", "email"], $conditions);
        $this->assertEquals(
            "UPDATE `User` SET `id` = ?, `email` = ? WHERE `User`.`id` = ?",
            "$updateQuery"
        );
    }

    #[Test]
    public function to_string_with_multiple_conditions_and() {
        $conditions = [
            new SQLCondition("`User`.`id` = ?"),
            new SQLCondition("`User`.`email` = ?"),
        ];
        $updateQuery = new UpdateQuery("User", ["id", "email"], $conditions);
        $this->assertEquals(
            "UPDATE `User` SET `id` = ?, `email` = ? WHERE `User`.`id` = ? AND `User`.`email` = ?",
            "$updateQuery"
        );
    }

    #[Test]
    public function to_string_with_multiple_conditions_or() {
        $conditions = [
            new SQLCondition("`User`.`id` = ?"),
            new SQLCondition("`User`.`email` = ?", SQLOperator::Or),
        ];
        $updateQuery = new UpdateQuery("User", ["id", "email"], $conditions);
        $this->assertEquals(
            "UPDATE `User` SET `id` = ?, `email` = ? WHERE `User`.`id` = ? OR `User`.`email` = ?",
            "$updateQuery"
        );
    }
}