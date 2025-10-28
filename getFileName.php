<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\getDocsData.php

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

    $airNo = $_GET['airNo'] ?? '';
    $types = $_GET['types'] ?? '';

    // Query the database for the user
    $sql = "SELECT id AS idx, fileName FROM files WHERE airNo = ? AND formType = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $airNo, $types);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    if (count($items) > 0) {
        echo json_encode([
            "success" => true,
            "data" => $items
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "No files found."
        ]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>