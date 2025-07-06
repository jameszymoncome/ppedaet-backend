<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\db_connection.php

class Database {
    private $host = "localhost";
    private $user = "root";
    private $password = "";
    private $database = "daetppe_db";
    private $conn;

    public function __construct() {
        $this->conn = new mysqli($this->host, $this->user, $this->password, $this->database);

        if ($this->conn->connect_error) {
            die("Database connection failed: " . $this->conn->connect_error);
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

function getDatabaseConnection() {
    static $db = null;
    if ($db === null) {
        $db = new Database();
    }
    return $db->getConnection();
}
?>