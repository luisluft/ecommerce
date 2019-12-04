<?php

namespace Hcode\DB;

use PDO;

class Sql
{
    const HOSTNAME = "50.116.112.41";
    const USERNAME = "tecluf21_user";
    const PASSWORD = "1YVH3Fplm5Cf";
    const DBNAME = "tecluf21_db";

    private $conn;

    public function __construct()
    {
        $this->conn = new PDO(
            "mysql:dbname=" . Sql::DBNAME . ";host=" . Sql::HOSTNAME,
            Sql::USERNAME,
            Sql::PASSWORD
        );
    }

    private function setParams($statement, $parameters = array())
    {
        foreach ($parameters as $key => $value) {
            $this->bindParam($statement, $key, $value);
        }
    }

    private function bindParam($statement, $key, $value)
    {
        $statement->bindParam($key, $value);
    }

    /**
     * Prepares the query statement,
     * Binds each parameter to the query,
     * and finally executes the query
     *
     * @param string $rawQuery SQL query to be executed
     *
     * @param array $params [optional] each of the parameters to be bound to the query
     *
     * @return void
     */
    public function query($rawQuery, $params = array())
    {
        $stmt = $this->conn->prepare($rawQuery);

        $this->setParams($stmt, $params);

        $stmt->execute();
    }

    public function select($rawQuery, $params = array()): array
    {
        $stmt = $this->conn->prepare($rawQuery);

        $this->setParams($stmt, $params);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
