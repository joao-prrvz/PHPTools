<?php
namespace PHPTools\Tests\Models;

use DateTime;
use PHPTools\ORM\Attributes as DB;
use PHPTools\ORM\DBCollection;

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