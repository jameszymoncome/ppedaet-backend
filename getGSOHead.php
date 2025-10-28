<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\getGSOHead.php

error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connection.php';

try {
    // Get database connection
    $database = new Database();
    $conn = $database->conn;

    $sql = "SELECT CONCAT(firstname, ' ', middlename, ' ', lastname) as fullname, position FROM users WHERE role = 'SUPER ADMIN' AND department = 'GSO' AND position = 'DEPARTMENT HEAD'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $head = [
            "fullname" => $row['fullname'],
            "position" => $row['position']
        ];
        echo json_encode(["head" => $head]);
    } else {
        echo json_encode(["success" => false, "message" => "No data found"]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>