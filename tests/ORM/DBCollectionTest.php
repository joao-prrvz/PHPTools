<?php
namespace PHPTools\Tests\ORM;

use DateTime;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;


class DBCollectionTest extends TestCase {
    private ?DBTestContext $_ctx = null;
    public DBTestContext $ctx {
        get {
            if ($this->_ctx === null)
                $this->_ctx = new DBTestContext();
            return $this->_ctx;
        }
    }

    #[Test]
    public function select_all() {
        $count = $this->ctx->users->length;
        $result = (int)$this->ctx->run("SELECT COUNT(*) FROM User")->fetch()["COUNT(*)"];
        $this->assertEquals($count, $result);
    }

    #[Test]
    public function select_by_attribute_existing() {
        $user = $this->ctx->users->where(fn($u) => $u->email === "alice@example.com")->first();
        $this->assertNotNull($user);
    }

    #[Test]
    public function select_by_attribute_not_existing() {
        $user = $this->ctx->users->where(fn($u) => $u->email === null)->first();
        $this->assertNull($user);
    }
    #[Test]
    public function select_all_entites_by_fk() {
        $pets = $this->ctx->pets->where(fn($p) => $p->userId === 1);
        $this->assertCount(3, $pets);
    }

    #[Test]
    public function sql_timestamp_php_date() {
        $user = $this->ctx->users->first();
        $this->assertInstanceOf(DateTime::class, $user->createAt);
    }

    public function getTime(callable $callable): float {
        $start = microtime(true);
        $callable();
        $end = microtime(true);
        return ($end - $start) * 1000;
    }
}