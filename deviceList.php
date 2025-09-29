<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\deviceList.php

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

    // ✅ Only select device_name
    $sql = "SELECT device_name, ip, last_seen, status FROM devices";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $deviceList = [];
    while ($row = $result->fetch_assoc()) {
        $deviceList[] = [
            'device_name' => $row['device_name'],
            'ip' => $row['ip'],
            'last_seen' => $row['last_seen'],
            'status' => $row['status']

        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $deviceList
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
