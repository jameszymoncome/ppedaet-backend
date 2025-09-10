<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\historyitem.php

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

    $propertyNos = $_GET['propertyNos'] ?? '';

    $sql = "SELECT
                COALESCE(par.description, ics.description) AS descriptions,
                COALESCE(par.model, ics.model) AS model,
                COALESCE(par.serialNo, ics.serialNo) AS serialNo,
                inspectionhistory.conditions,
                inspectionhistory.remarks,
                inspectionhistory.dateInspected
            FROM inspectionhistory
            LEFT JOIN par ON par.tagID = inspectionhistory.tagID
            LEFT JOIN ics ON ics.tagID =inspectionhistory.tagID
            WHERE COALESCE(par.serialNo, ics.serialNo) = ?

    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $propertyNos);
    $stmt->execute();
    $result = $stmt->get_result();

    $historys = [];
    while ($row = $result->fetch_assoc()) {
        $historys[] = $row;
    }

    echo json_encode([
        "success" => true,
        "data" => $historys
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>