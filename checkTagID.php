<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\checkTagID.php

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

    $nfcId = $_GET['nfcId'] ?? '';
    $propertyNo = $_GET['propertyNo'] ?? '';
    $tableType = $_GET['tableType'] ?? '';

    $sql = "
        SELECT 'ics' AS source, tagID FROM ics WHERE tagID = ?
        UNION
        SELECT 'par' AS source, tagID FROM par WHERE tagID = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $nfcId, $nfcId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $tables = [];
        while ($row = $result->fetch_assoc()) {
            $tables[] = strtoupper($row['source']); // 'ics' or 'par' → 'ICS' or 'PAR'
        }

        echo json_encode([
            "success" => false,
            "message" => "❌ Tag ID already exists in: " . implode(" and ", $tables)
        ]);
        exit();
    }

    if ($tableType === "ICS") {
        $stmt = $conn->prepare("UPDATE ics SET tagID = ?, status = 'Done Tagging' WHERE inventoryNo = ?");
    } else if ($tableType === "PAR") {
        $stmt = $conn->prepare("UPDATE par SET tagID = ?, status = 'Done Tagging' WHERE propertyNo = ?");
    }

    $stmt->bind_param("ss", $nfcId, $propertyNo);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode([
            "success" => true,
            "message" => "✅ Tag ID updated successfully in $tableType table."
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "❌ No matching property number found in $tableType table."
        ]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>