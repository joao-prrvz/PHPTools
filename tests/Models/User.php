<?php
namespace PHPTools\Tests\Models;

use DateTime;
use PHPTools\ORM\Attributes as DB;

class User {
    #[DB\Block]
    public int $id;
    public string $email;
    public string $name;
    #[DB\Column("created_at"), DB\Block, DB\Date("Y-m-d H:i:s")]
    public DateTime $createAt;

    public UserType $type;

}