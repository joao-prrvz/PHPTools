<?php
namespace PHPTools\Tests\ORM;

use PHPTools\ORM\DBCollection;
use PHPTools\ORM\DBContext;
use PHPTools\Tests\Models\Pet;
use PHPTools\Tests\Models\User;

class DBTestContext extends DBContext {
    /** @var DBCollection<User>*/
    public DBCollection $users;
    /** @var DBCollection<Pet>*/
    public DBCollection $pets;

    public function __construct() {
        if (file_exists(__DIR__ . "/test.db"))
            unlink(__DIR__ . "/test.db");

        parent::__construct("sqlite:" . __DIR__ . "/test.db"); // then connect (creates fresh file)

        $this->users = new DBCollection(User::class, $this);
        $this->pets  = new DBCollection(Pet::class, $this);

        $sql = file_get_contents(__DIR__ . "/../seed.sql");
        $this->db->exec($sql);
    }
}