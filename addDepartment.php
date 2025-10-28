<?php
// addDepartment.php

error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connection.php';

$data = json_decode(file_get_contents("php://input"), true);
$dept_id = trim($data['dept_id'] ?? '');
$name = trim($data['name'] ?? '');
$address = trim($data['address'] ?? '');

if (empty($name) || empty($address)) {
    echo json_encode(['success' => false, 'message' => 'Both name and address are required.']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->conn;
    $stmt = $conn->prepare("INSERT INTO departmenttbl (dept_id, entity_name, dept_address) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $dept_id, $name, $address);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Department added successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add department.']);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
