<?php
namespace PHPTools\ORM;

use PDO;

abstract class DBContext {
    private PDO $pdo;
    public PDO $db { get => $this->pdo; }
    
    public function __construct(
        string $dsn, 
        string|null $username = null,
        string|null $password = null,
        array|null $options = null
    ) {
        $this->pdo = new PDO($dsn, $username, $password, $options);
    }

    public function run(string $sql, array $params = []) {
        $sttmt = $this->pdo->prepare($sql);
        $sttmt->execute($params);
        return $sttmt;
    }
}