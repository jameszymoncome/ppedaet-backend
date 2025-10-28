<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\taggedAssetList.php

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

    $departmentName = $_GET['departmentName'] ?? '';

    $sql = "SELECT
            COALESCE(par.propertyNo, ics.inventoryNo) AS propertyID,
            COALESCE(par.description, ics.description) AS description,
            COALESCE(par.model, ics.model) AS model,
            COALESCE(par.serialNo, ics.serialNo) AS serialNo,
            inspectionhistory.conditions,
            inspectionhistory.dateInspected
            FROM users
            INNER JOIN air_items ON air_items.enduser_id = users.user_id
            LEFT JOIN par ON par.airNo = air_items.air_no
            LEFT JOIN ics ON ics.airNo = air_items.air_no
            LEFT JOIN inspectionhistory ON inspectionhistory.tagID = COALESCE(par.tagID, ics.tagID)
            WHERE users.department = ? AND COALESCE(par.status, ics.status) = 'Assigned'

    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $departmentName);
    $stmt->execute();
    $result = $stmt->get_result();

    $taggedAsseetList = [];
    while ($row = $result->fetch_assoc()) {
        $taggedAsseetList[] = $row;
    }

    echo json_encode([
        "success" => true,
        "data" => $taggedAsseetList
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>