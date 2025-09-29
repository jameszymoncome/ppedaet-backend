<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\getItemTag.php

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

    $tag = $_GET['tag'] ?? '';

    $sql = "
        SELECT
            COALESCE(par.propertyNo, ics.inventoryNo) AS docNo,
            COALESCE(par.propertyNo, ics.inventoryNo) AS itemID,
            COALESCE(par.tagID, ics.tagID) AS tagID,
            COALESCE(par.description, ics.description) AS description,
            COALESCE(par.model, ics.model) AS model,
            COALESCE(par.serialNo, ics.serialNo) AS serialNo,
            COALESCE(par.article, ics.article) AS category,
            users.department,
            ih.conditions,
            ih.remarks,
            ih.dateInspected
            FROM air_items
            LEFT JOIN par ON par.airNo = air_items.air_no
            LEFT JOIN ics ON ics.airNo = air_items.air_no
            INNER JOIN users ON users.user_id = air_items.enduser_id
            LEFT JOIN inspectionhistory ih ON ih.tagID = COALESCE(par.tagID, ics.tagID)
            WHERE ih.tagID = ?
            ORDER BY ih.dateInspected DESC;
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $tag);
    $stmt->execute();
    $result = $stmt->get_result();

    $scanned = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $scanned[] = $row;
        }
        echo json_encode([
            'success' => true,
            'data' => $scanned
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No data found.'
        ]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>