<?php

namespace framework;

class ModelFactory
{
    private $conn  = null;
    private $host;
    private $database;
    private $user;
    private $pass;

    function __construct($host, $database, $user, $pass)
    {
        $this->host = $host;
        $this->database = $database;
        $this->user = $user;
        $this->pass = $pass;
    }

    public function connect()
    {
        if (!$this->conn) {
            $this->conn = new \PDO('mysql:host=' . $this->host . ';dbname=' . $this->database, $this->user, $this->pass);
            $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }

        return $this->conn;
    }

    // instantiate a model with an optional database connection
    public function build($model, $database = false)
    {
        $obj = __NAMESPACE__ . '\\models\\' . ucfirst(strtolower($model));
        if (class_exists($obj)) {
            if ($database) {
                $conn = self::connect();
                return new $obj($conn);
            } else {
                return new $obj();
            }
        }
    }
}
