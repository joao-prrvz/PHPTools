<?php
namespace PHPTools\Tests\ORM\Queries;

use PHPTools\ORM\Queries\SelectQuery;
use PHPTools\ORM\Queries\SQLCondition;
use PHPTools\ORM\Queries\SQLOperator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SelectQueryTest extends TestCase {

    #[Test]
    public function to_string() {
        $selectQuery = new SelectQuery("User", ["`User`.`id`", "`User`.`email`"]);
        $this->assertEquals("SELECT `User`.`id`, `User`.`email` FROM `User`", "$selectQuery");
    }

    #[Test]
    public function to_string_with_condition_and() {
        $conditions = [
            new SQLCondition("`User`.`id` = ?"),
            new SQLCondition("`User`.`email` = ?"),
        ];
        $selectQuery = new SelectQuery("User", ["`User`.`id`", "`User`.`email`"], $conditions);
        $this->assertEquals("SELECT `User`.`id`, `User`.`email` FROM `User` WHERE `User`.`id` = ? AND `User`.`email` = ?", "$selectQuery");
    }

    #[Test]
    public function to_string_with_condition_or() {
        $conditions = [
            new SQLCondition("`User`.`id` = ?"),
            new SQLCondition("`User`.`email` = ?", SQLOperator::Or),
        ];
        $selectQuery = new SelectQuery("User", ["`User`.`id`", "`User`.`email`"], $conditions);
        $this->assertEquals("SELECT `User`.`id`, `User`.`email` FROM `User` WHERE `User`.`id` = ? OR `User`.`email` = ?", "$selectQuery");
    }
}