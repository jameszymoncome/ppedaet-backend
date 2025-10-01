<?php
// getDepartments.php

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
    // Get the database connection
    $conn = getDatabaseConnection();

    $query = "SELECT `dept_id`, `entity_name`, `dept_address`, `status` FROM `departmenttbl`";
    $result = $conn->query($query);

    if (!$result) {
        echo json_encode(["success" => false, "message" => "Error fetching departments: " . $conn->error]);
        exit();
    }

    $departments = [];
    while ($row = $result->fetch_assoc()) {
        $departments[] = [
            "id" => $row["dept_id"],
            "name" => $row["entity_name"],
            "address" => $row["dept_address"],
            "status" => $row["status"],
        ];
    }

    echo json_encode(["success" => true, "departments" => $departments]);

    $conn->close();
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Server error: " . $e->getMessage()]);
}
?>
