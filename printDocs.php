<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\printDocs.php

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

    $docsNo = $_GET['docsNo'] ?? '';
    $typess = $_GET['typess'] ?? '';

    // Query the database for the user
    $sql = "SELECT
                ai.air_no,
                ai.air_date,
                ai.fund,
                par.parNo AS docsNo,
                par.unit AS unit,
                par.article AS article,
                par.description AS description,
                par.model AS model,
                par.serialNo AS serialNo,
                par.propertyNo AS itemNOs,
                DATE(ai.created_at) AS dateAcquired,
                par.unitCost AS unitCost,
                CONCAT(users.firstname, ' ', users.middlename, ' ', users.lastname) AS enduserName,
                categorycode.usefulness
            FROM air_items ai
            LEFT JOIN par ON par.airNo = ai.air_no
            LEFT JOIN users ON users.user_id = ai.enduser_id
            LEFT JOIN categorycode ON categorycode.categoryID = par.articleCode
            WHERE par.parNo = ?
            AND par.type = ?

            UNION ALL

            SELECT
                ai.air_no,
                ai.air_date,
                ai.fund,
                ics.icsNo AS docsNo,
                ics.unit AS unit,
                ics.article AS article,
                ics.description AS description,
                ics.model AS model,
                ics.serialNo AS serialNo,
                ics.inventoryNo AS itemNOs,
                DATE(ai.created_at) AS dateAcquired,
                ics.unitCost AS unitCost,
                CONCAT(users.firstname, ' ', users.middlename, ' ', users.lastname) AS enduserName,
                categorycode.usefulness
            FROM air_items ai
            LEFT JOIN ics ON ics.airNo = ai.air_no
            LEFT JOIN users ON users.user_id = ai.enduser_id
            LEFT JOIN categorycode ON categorycode.categoryID = ics.articleCode
            WHERE ics.icsNo = ?
            AND ics.type = ?;
            ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $docsNo, $typess, $docsNo, $typess);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    echo json_encode($items);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>