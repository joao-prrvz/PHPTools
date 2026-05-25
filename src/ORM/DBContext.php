<?php
namespace PHPTools\ORM;

use PDO;

abstract class DBContext {
    private PDO $pdo;
    public PDO $db { get => $this->pdo; }
    
    public function __construct(string $dsn) {
        $this->pdo = new PDO($dsn);
    }

    public function run(string $sql, array $params = []) {
        $sttmt = $this->pdo->prepare($sql);
        $sttmt->execute($params);
        return $sttmt;
    }
}