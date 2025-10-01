<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\getInspects.php
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

    $sql = "SELECT
                COALESCE(par.tagID, ics.tagID) AS tagID,
                COALESCE(par.propertyNo, ics.inventoryNo) AS docNo,
                COALESCE(par.description, ics.description) AS description,
                COALESCE(par.model, ics.model) AS model,
                COALESCE(par.serialNo, ics.serialNo) AS serialNo,
                users.department,
                inspectionhistory.dateInspected AS dateInspected,
                inspectionhistory.conditions,
                inspectionhistory.remarks
            FROM air_items
            LEFT JOIN par ON par.airNo = air_items.air_no
            LEFT JOIN ics ON ics.airNo = air_items.air_no
            INNER JOIN users ON users.user_id = air_items.enduser_id
            LEFT JOIN inspectionhistory ON inspectionhistory.tagID = COALESCE(par.tagID, ics.tagID)
            WHERE inspectionhistory.dateInspected IS NOT NULL AND inspectionhistory.conditions <> ''
            ORDER BY dateInspected DESC;
            ";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];

    $id = 1;

    while ($row = $result->fetch_assoc()) {
        $items[] = [
            "id" => $id++,
            "tagID" => $row['tagID'],
            "docNo" => $row['docNo'],
            "description" => $row['description'],
            "model" => $row['model'],
            "serialNo" => $row['serialNo'],
            "department" => $row['department'],
            "dateInspected" => $row['dateInspected'],
            "conditions" => $row['conditions'],
            "remarks" => $row['remarks']
        ];
    }

    if (count($items) > 0) {
        echo json_encode(["items" => $items]);
    } else {
        echo json_encode(["success" => false, "message" => "No data found"]);
    }

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
