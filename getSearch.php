<?php
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
    $database = new Database();
    $conn = $database->conn;

    $sql = "WITH combined AS (
                SELECT 
                    par.propertyNo AS propertyNo,
                    CONCAT(par.description, par.model) AS itemName,
                    par.tagID AS tagID
                FROM air_items ai
                JOIN par ON par.airNo = ai.air_no
                WHERE par.status = 'Assigned'

                UNION ALL

                SELECT 
                    ics.inventoryNo AS propertyNo,
                    CONCAT(ics.description, ics.model) AS itemName,
                    ics.tagID AS tagID
                FROM air_items ai
                JOIN ics ON ics.airNo = ai.air_no
                WHERE ics.status = 'Assigned'
            )
            SELECT 
                c.propertyNo,
                c.itemName,
                c.tagID,
                CASE
                    WHEN ih.tagID IS NULL THEN 'New Added'
                    WHEN (ih.conditions = '' OR ih.conditions IS NULL) AND ih.updates <> '' THEN ih.updates
                    ELSE ih.conditions
                END AS stats
            FROM combined c
            LEFT JOIN inspectionhistory ih 
                ON ih.tagID = c.tagID
                AND ih.dateInspected = (
                    SELECT MAX(ih2.dateInspected)
                    FROM inspectionhistory ih2
                    WHERE ih2.tagID = c.tagID
                )
            ORDER BY c.itemName;";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            "propertyNo" => $row['propertyNo'],
            "itemName" => $row['itemName'],
            "tagID" => $row['tagID'],
            "stats" => $row['stats']
        ];
    }

    echo json_encode([
        "success" => true,
        "data" => $items
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>
