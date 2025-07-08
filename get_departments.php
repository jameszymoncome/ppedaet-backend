<?php
// get_departments.php

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
    $conn = getDatabaseConnection();

    $query = "SELECT * FROM `departmenttbl`";
    $result = $conn->query($query);

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch departments: ' . $conn->error]);
        exit();
    }

    $departments = [];
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $departments]);

    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
