<?php
namespace PHPTools\Tests\ORM;

use PHPTools\ORM\Queries\InsertQuery;
use PHPTools\ORM\Queries\SelectQuery;
use PHPTools\ORM\Queries\SQLCondition;
use PHPTools\ORM\Queries\UpdateQuery;
use PHPTools\ORM\SQLBuilder;
use PHPTools\Tests\Models\User;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionObject;

class SQLBuilderTest extends TestCase {

    #[Test]
    public function get_table() {
        $builder = new SQLBuilder(User::class);
        $this->assertEquals("User", $builder->table);
    }

    #[Test]
    public function get_columns() {
        $builder = new SQLBuilder(User::class);
        $columns = $builder->columns;
        $this->assertEquals(["id", "email", "name", "created_at"], array_values($columns));
    }

    #[Test]
    public function get_columns_to_insert() {
        $builder = new SQLBuilder(User::class);
        $columns = $builder->columnsToInsert;
        $this->assertEquals(["email", "name"], array_values($columns));
    }

    #[Test]
    public function get_columns_to_update() {
        $builder = new SQLBuilder(User::class);
        $columns = $builder->columnsToUpdate;
        $this->assertEquals(["email", "name"], array_values($columns));
    }

    #[Test]
    public function build_select_all_query() {
        $ctx = new DBTestContext();
        $ref = new ReflectionClass($ctx->users);
        /** @var SQLBuilder */
        $builder = $ref->getProperty("builder")->getValue($ctx->users);
        $query = $builder->buildQuery();
        $this->assertEquals(
            "SELECT `User`.`id`, `User`.`email`, `User`.`name`, `User`.`created_at` FROM `User`",
            $query
        );
    }

    #[Test]
    public function build_select_where_query() {
        $ctx = new DBTestContext();
        $users = $ctx->users->where(fn($u) => $u->email === "alice@example.com");
        $ref = new ReflectionClass($users);
        /** @var SQLBuilder */
        $builder = $ref->getProperty("builder")->getValue($users);
        $query = $builder->buildQuery();
        $this->assertEquals(
            "SELECT `User`.`id`, `User`.`email`, `User`.`name`, `User`.`created_at` FROM `User` WHERE `User`.`email` = ?",
            $query
        );
    }

    #[Test]
    public function params_select_where_query() {
        $ctx = new DBTestContext();
        $users = $ctx->users->where(fn($u) => $u->email === "alice@example.com");
        $ref = new ReflectionClass($users);
        /** @var SQLBuilder */
        $builder = $ref->getProperty("builder")->getValue($users);
        $this->assertEquals(
            ["alice@example.com"],
            $builder->params
        );
    }

    #[Test]
    public function build_select_where_query_and_operator() {
        $ctx = new DBTestContext();
        $users = $ctx->users->where(fn($u) => $u->email === "alice@example.com" && $u->id == 1);
        $ref = new ReflectionClass($users);
        /** @var SQLBuilder */
        $builder = $ref->getProperty("builder")->getValue($users);
        $query = $builder->buildQuery();
        $this->assertEquals(
            "SELECT `User`.`id`, `User`.`email`, `User`.`name`, `User`.`created_at` FROM `User` WHERE (`User`.`email` = ? AND `User`.`id` = ?)",
            $query
        );
    }

    #[Test]
    public function build_select_where_query_and_lambdas() {
        $ctx = new DBTestContext();
        $users = $ctx->users
            ->where(fn($u) => $u->email === "alice@example.com")
            ->where(fn($u) =>  $u->id == 1);
        $ref = new ReflectionClass($users);
        /** @var SQLBuilder */
        $builder = $ref->getProperty("builder")->getValue($users);
        $query = $builder->buildQuery();
        $this->assertEquals(
            "SELECT `User`.`id`, `User`.`email`, `User`.`name`, `User`.`created_at` FROM `User` WHERE `User`.`id` = ? AND `User`.`email` = ?",
            $query
        );
    }

    #[Test]
    public function build_select_where_query_or_operator() {
        $ctx = new DBTestContext();
        $users = $ctx->users->where(fn($u) => $u->email === "alice@example.com" || $u->id == 1);
        $ref = new ReflectionClass($users);
        /** @var SQLBuilder */
        $builder = $ref->getProperty("builder")->getValue($users);
        $query = $builder->buildQuery();
        $this->assertEquals(
            "SELECT `User`.`id`, `User`.`email`, `User`.`name`, `User`.`created_at` FROM `User` WHERE (`User`.`email` = ? OR `User`.`id` = ?)",
            $query
        );
    }

    #[Test]
    public function build_insert_query() {
        $builder = new SQLBuilder(User::class);
        $builder->query = new InsertQuery($builder->table, $builder->columnsToInsert, 1);
        $query = $builder->buildQuery();
        $this->assertEquals(
            "INSERT INTO `User` (`email`, `name`) VALUES (?, ?)",
            $query
        );
    }

    #[Test]
    public function build_insert_query_multiple() {
        $builder = new SQLBuilder(User::class);
        $builder->query = new InsertQuery($builder->table, $builder->columnsToInsert, 4);
        $query = $builder->buildQuery();
        $this->assertEquals(
            "INSERT INTO `User` (`email`, `name`) VALUES (?, ?), (?, ?), (?, ?), (?, ?)",
            $query
        );
    }

    #[Test]
    public function build_update_query() {
        $builder = new SQLBuilder(User::class);
        $builder->query = new UpdateQuery($builder->table, $builder->columnsToUpdate, [new SQLCondition("`User`.`id` = ?")]);
        $query = $builder->buildQuery();
        $this->assertEquals(
            "UPDATE `User` SET `email` = ?, `name` = ? WHERE `User`.`id` = ?",
            $query
        );
    }
}