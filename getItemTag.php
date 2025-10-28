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
    $database = new Database();
    $conn = $database->conn;

    $tag = $_GET['tag'] ?? '';

    $sql = "
        SELECT
            par.propertyNo AS docNo,
            par.propertyNo AS itemID,
            par.tagID AS tagID,
            par.description AS description,
            par.model AS model,
            par.serialNo AS serialNo,
            par.article AS category,
            users.department,
            CONCAT(users.firstname, ' ', users.middlename, ' ', users.lastname) AS assignedTo,
            ih.conditions,
            ih.remarks,
            ih.updates,
            ih.dateInspected,
            'PAR' AS sourceType
        FROM air_items
        JOIN par ON par.airNo = air_items.air_no
        INNER JOIN users ON users.user_id = air_items.enduser_id
        LEFT JOIN (
            SELECT ih1.tagID, ih1.conditions, ih1.remarks, ih1.updates, ih1.dateInspected
            FROM inspectionhistory ih1
            INNER JOIN (
                SELECT tagID, MAX(dateInspected) AS maxDate
                FROM inspectionhistory
                GROUP BY tagID
            ) ih2 ON ih1.tagID = ih2.tagID AND ih1.dateInspected = ih2.maxDate
        ) ih ON ih.tagID = par.tagID
        WHERE par.tagID = ? AND (ih.conditions IS NULL OR ih.conditions != 'Scrap Condition') AND par.status = 'Assigned'

        UNION ALL

        SELECT
            ics.inventoryNo AS docNo,
            ics.inventoryNo AS itemID,
            ics.tagID AS tagID,
            ics.description AS description,
            ics.model AS model,
            ics.serialNo AS serialNo,
            ics.article AS category,
            users.department,
            CONCAT(users.firstname, ' ', users.middlename, ' ', users.lastname) AS assignedTo,
            ih.conditions,
            ih.remarks,
            ih.updates,
            ih.dateInspected,
            'ICS' AS sourceType
        FROM air_items
        JOIN ics ON ics.airNo = air_items.air_no
        INNER JOIN users ON users.user_id = air_items.enduser_id
        LEFT JOIN (
            SELECT ih1.tagID, ih1.conditions, ih1.remarks, ih1.updates, ih1.dateInspected
            FROM inspectionhistory ih1
            INNER JOIN (
                SELECT tagID, MAX(dateInspected) AS maxDate
                FROM inspectionhistory
                GROUP BY tagID
            ) ih2 ON ih1.tagID = ih2.tagID AND ih1.dateInspected = ih2.maxDate
        ) ih ON ih.tagID = ics.tagID
        WHERE ics.tagID = ? AND (ih.conditions IS NULL OR ih.conditions != 'Scrap Condition') AND ics.status = 'Assigned'

    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $tag, $tag);
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