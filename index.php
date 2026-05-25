<?php

use PHPTools\ORM\Attributes as DB;
use PHPTools\ORM\DBCollection;
use PHPTools\ORM\DBContext;

require __DIR__."/vendor/autoload.php";

class User {
    #[DB\Block]
    public int $id;
    public string $email;
    public string $name;
    #[DB\Column("created_at"), DB\Block]
    private string $_createdAt;

    #[DB\Ignore]
    public DateTime $createAt {
        get => DateTime::createFromFormat(DBCollection::TIMESTAMP_FORMAT, $this->_createdAt);
        set => $value->format(DBCollection::TIMESTAMP_FORMAT);
    }
}
class Pet {
    public int $id;
    public string $name;
    public int $userId;
}

class UserContext extends DBContext {
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

        $sql = file_get_contents(__DIR__ . "/tests/seed.sql");
        $this->db->exec($sql);
    }
}

$ctx = new UserContext();

$user = $ctx->users->first();
$user->name = "Alice Updated";
$ctx->users->update($user);