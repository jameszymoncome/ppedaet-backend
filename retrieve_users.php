<?php
// retrieve_users.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connection.php';

try {
    // Get DB connection
    $conn = getDatabaseConnection();

    // Query all users
    $sql = "
        SELECT user_id, 
               CONCAT(lastname, ', ', firstname, ' ', middlename) AS full_name,
               department, role, email, contactNumber, position
        FROM users
        ORDER BY lastname ASC
    ";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode(["success" => true, "data" => $users]);

    $conn->close();

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error retrieving users: " . $e->getMessage()
    ]);
}
