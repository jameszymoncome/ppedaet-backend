<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\taggedAssets.php

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

    $sql = "SELECT 
                departmenttbl.entity_name AS department,
                COUNT(inspectionhistory.tagID) AS total_count
            FROM departmenttbl
            LEFT JOIN users
                ON users.department = departmenttbl.entity_name
            LEFT JOIN air_items
                ON air_items.enduser_id = users.user_id
            LEFT JOIN par
                ON par.airNo = air_items.air_no
            LEFT JOIN ics
                ON ics.airNo = air_items.air_no
            LEFT JOIN inspectionhistory
                ON inspectionhistory.tagID = COALESCE(par.tagID, ics.tagID)
            GROUP BY departmenttbl.entity_name
            ORDER BY total_count DESC;
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $taggedAssets = [];
    while ($row = $result->fetch_assoc()) {
        $taggedAssets[] = $row;
    }

    echo json_encode([
        "success" => true,
        "data" => $taggedAssets
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>