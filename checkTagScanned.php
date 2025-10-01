<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\checkTagScanned.php

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
    $conn = getDatabaseConnection();

    $nfcId = $_GET['nfcId'] ?? '';

    if (empty($nfcId)) {
        echo json_encode([
            "success" => false,
            "message" => "No tag ID provided."
        ]);
        exit();
    }

    $sql = "
        SELECT 'ICS' AS source, tagID FROM ics WHERE tagID = ?
        UNION
        SELECT 'PAR' AS source, tagID FROM par WHERE tagID = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $nfcId, $nfcId);
    $stmt->execute();
    $result = $stmt->get_result();

    $matches = [];
    while ($row = $result->fetch_assoc()) {
        $matches[] = $row['source']; // will be "ICS" or "PAR"
    }

    if (count($matches) > 0) {
        echo json_encode([
            "success" => true,
            "exists" => true,
            "message" => "❌ Tag ID already exists in: " . implode(" and ", $matches),
            "sources" => $matches
        ]);
    } else {
        echo json_encode([
            "success" => true,
            "exists" => false,
            "message" => "✅ Tag ID not found. Safe to use."
        ]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>