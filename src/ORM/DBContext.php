<?php
namespace PHPTools\ORM;

use PDO;
use PDOStatement;

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
        $this->bindValues($sttmt, $params);
        $sttmt->execute();
        return $sttmt;
    }

    private function bindValues(PDOStatement $sttmt, array $params) {
        foreach ($params as $i => $value) {
            $type = match(true) {
                is_bool($value) => PDO::PARAM_BOOL,
                is_int($value)  => PDO::PARAM_INT,
                is_null($value) => PDO::PARAM_NULL,
                default          => PDO::PARAM_STR,
            };
            $sttmt->bindValue($i + 1, $value, $type);
        }   
    }
}
