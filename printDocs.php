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
    $conn = getDatabaseConnection();

    $docsNo = $_GET['docsNo'] ?? '';
    $typess = $_GET['typess'] ?? '';

    // Query the database for the user
    $sql = "SELECT
                ai.air_no,
                ai.air_date,
                ai.fund,
                COALESCE(par.unit, ics.unit) AS unit,
                COALESCE(par.article, ics.article) AS article,
                COALESCE(par.description, ics.description) AS description,
                COALESCE(par.model, ics.model) AS model,
                COALESCE(par.serialNo, ics.serialNo) AS serialNo,
                COALESCE(par.propertyNo, ics.inventoryNo) AS itemNOs,
                DATE(ai.created_at) AS dateAcquired,
                COALESCE(par.unitCost, ics.unitCost) AS unitCost,
                CONCAT(users.firstname, ' ', users.middlename, ' ', users.lastname) as enduserName
            FROM air_items ai
            LEFT JOIN par ON par.airNo = ai.air_no
            LEFT JOIN ics ON ics.airNo = ai.air_no
            LEFT JOIN users ON users.user_id = ai.enduser_id
            WHERE COALESCE(par.parNo, ics.icsNo) = ?
            AND COALESCE(par.type, ics.type) = ?;
            ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $docsNo, $typess);
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