<?php
namespace PHPTools\Tests\ORM;

use PHPTools\ORM\Queries\SelectQuery;
use PHPTools\ORM\SQLBuilder;
use PHPTools\Tests\Models\User;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;


class SQLBuilderTest extends TestCase {
    #[Test]
    public function get_columns() {
        $builder = new SQLBuilder(User::class);
        $columns = $builder->columns;
        $this->assertArrayHasKey("email", $columns);
        $this->assertArrayHasKey("name", $columns);
        $this->assertArrayHasKey("id", $columns);
    }

    #[Test]
    public function get_columns_to_insert() {
        $builder = new SQLBuilder(User::class);
        $columns = $builder->columnsToInsert;
        $this->assertContains("email", $columns);
        $this->assertContains("name", $columns);
        $this->assertNotContains("id", $columns);
        $this->assertNotContains("created_at", $columns);
    }

    #[Test]
    public function get_table() {
        $builder = new SQLBuilder(User::class);
        $this->assertEquals("User", $builder->table);
    }

    #[Test]
    public function format_columns() {
        $builder = new SQLBuilder(User::class);
        $method = new \ReflectionMethod(SelectQuery::class, "formatColumns");
        $result = $method->invoke($builder->query);
        $this->assertEquals("`User`.`id`, `User`.`email`, `User`.`name`, `User`.`created_at`", $result);
    }

    #[Test]
    public function build_select_query() {
        $builder = new SQLBuilder(User::class);
        $query = $builder->buildQuery();
        $this->assertEquals("SELECT `User`.`id`, `User`.`email`, `User`.`name`, `User`.`created_at` FROM `User`", $query);
    }

    #[Test]
    public function get_primary_key() {
        $builder = new SQLBuilder(User::class);
        $key = array_values($builder->primaryKeys)[0] ?? null;
        $this->assertEquals("id", $key);
    }


    #[Test]
    public function parse_where_static_value() {
        $builder = new SQLBuilder(User::class);
        $ctx = new DBTestContext();
        $predicate = fn($u) => $u->email == "johndoe@example.com";
        $ctx->users->where($predicate);
        $builder->parseWhere($predicate);
        $this->assertEquals(
            "SELECT `User`.`id`, `User`.`email`, `User`.`name`, `User`.`created_at` FROM `User` WHERE `User`.`email` == ?",
            "$builder->query"
        );
    }

    #[Test]
    public function parse_where_null_static_value() {
        $builder = new SQLBuilder(User::class);
        $ctx = new DBTestContext();
        $predicate = fn($u) => $u->email == null;
        $ctx->users->where($predicate);
        $builder->parseWhere($predicate);
        $this->assertEquals(
            "SELECT `User`.`id`, `User`.`email`, `User`.`name`, `User`.`created_at` FROM `User` WHERE `User`.`email` IS ?",
            "$builder->query"
        );
    }

    #[Test]
    public function parse_where_param_value() {
        $builder = new SQLBuilder(User::class);
        $ctx = new DBTestContext();
        $email = "johndoe@example.com";
        $predicate = fn($u) => $u->email == $email;
        $ctx->users->where($predicate);
        $builder->parseWhere($predicate);
        $this->assertEquals(
            "SELECT `User`.`id`, `User`.`email`, `User`.`name`, `User`.`created_at` FROM `User` WHERE `User`.`email` == ?",
            "$builder->query"
        );
    }

    #[Test]
    public function parse_where_null_param_value() {
        $builder = new SQLBuilder(User::class);
        $email = null;
        $predicate = fn($u) => $u->email == $email;
        $builder->parseWhere($predicate);
        $this->assertEquals(
            "SELECT `User`.`id`, `User`.`email`, `User`.`name`, `User`.`created_at` FROM `User` WHERE `User`.`email` IS ?",
            "$builder->query"
        );
    }

    #[Test]
    public function parse_where_static_value_lambda() {
        $ctx = new DBTestContext();
        $users = $ctx->users->where(fn($u) => $u->email === "johndoe@example.com");
        $query = $users->builder->buildQuery();
        $this->assertEquals(
            "SELECT `User`.`id`, `User`.`email`, `User`.`name`, `User`.`created_at` FROM `User` WHERE `User`.`email` == ?",
            $query
        );
    }

        #[Test]
    public function parse_where_null_static_value_lambda() {
        $ctx = new DBTestContext();
        $users = $ctx->users->where(fn($u) => $u->email === null);
        $query = $users->builder->buildQuery();
        $this->assertEquals(
            "SELECT `User`.`id`, `User`.`email`, `User`.`name`, `User`.`created_at` FROM `User` WHERE `User`.`email` IS ?",
            $query
        );
    }

    #[Test]
    public function parse_where_param_value_lambda() {
        $ctx = new DBTestContext();
        $email = "johndoe@example.com";
        $users = $ctx->users->where(fn($u) => $u->email === $email);
        $query = $users->builder->buildQuery();
        $this->assertEquals(
            "SELECT `User`.`id`, `User`.`email`, `User`.`name`, `User`.`created_at` FROM `User` WHERE `User`.`email` == ?",
            $query
        );
    }

    #[Test]
    public function parse_where_null_param_value_lambda() {
        $ctx = new DBTestContext();
        $email = null;
        $users = $ctx->users->where(fn($u) => $u->email === $email);
        $query = $users->builder->buildQuery();
        $this->assertEquals(
            "SELECT `User`.`id`, `User`.`email`, `User`.`name`, `User`.`created_at` FROM `User` WHERE `User`.`email` IS ?",
            $query
        );
    }
}