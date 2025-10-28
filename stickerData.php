<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\stickerData.php

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

    $ids = $_GET['ids'] ?? '';

    $sql = "
        SELECT
            COALESCE(par.propertyNo, ics.inventoryNo) AS item_id,
            users.department,
            COALESCE(par.article, ics.article) AS articles,
            COALESCE(par.serialNo, ics.serialNo) AS serial_no,
            CASE 
                WHEN ih.conditions = 'Scrap Condition' THEN 'Unserviceable'
                ELSE 'Serviceable'
            END AS status,
            CONCAT(
                ROW_NUMBER() OVER (
                PARTITION BY air_items.air_no, air_items.air_date, air_items.fund,
                            COALESCE(par.description, ics.description),
                            COALESCE(par.article, ics.article),
                            COALESCE(par.model, ics.model)
                ORDER BY COALESCE(par.propertyNo, ics.inventoryNo)
                ),
                ' of ',
                COUNT(*) OVER (
                PARTITION BY air_items.air_no, air_items.air_date, air_items.fund,
                            COALESCE(par.description, ics.description),
                            COALESCE(par.article, ics.article),
                            COALESCE(par.model, ics.model)
                )
            ) AS quantity,
            COALESCE(par.unitCost, ics.unitCost) AS unit_price,
            air_items.air_date AS date_acquisition,
            air_items.created_at AS date,
            CONCAT(users.lastname, ', ', users.firstname, ' ', users.middlename) AS custodian_name
        FROM
            air_items
        LEFT JOIN par ON par.airNo = air_items.air_no
        LEFT JOIN ics ON ics.airNo = air_items.air_no
        INNER JOIN users ON users.user_id = air_items.enduser_id
        LEFT JOIN inspectionhistory ih 
        ON ih.tagID = COALESCE(par.propertyNo, ics.inventoryNo)
        AND ih.dateInspected = (
            SELECT MAX(dateInspected)
            FROM inspectionhistory
            WHERE tagID = COALESCE(par.propertyNo, ics.inventoryNo)
        )
        WHERE air_items.air_no = ?;


    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ids);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'item_id' => $row['item_id'],
                'department' => $row['department'],
                'articles' => $row['articles'],
                'serial_no' => $row['serial_no'],
                'quantity' => $row['quantity'], // this is the "x of y" format
                'unit_price' => $row['unit_price'],
                'date_acquisition' => $row['date_acquisition'],
                'date' => $row['date'], // latest inspection or fallback to ppe_entries.date
                'custodian_name' => $row['custodian_name']
            ];
        }
        echo json_encode(["data" => $data]);
    } else {
        echo json_encode(["data" => []]);
    }

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
