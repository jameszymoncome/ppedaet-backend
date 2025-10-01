<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\getDocsData.php

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

    $docNo = $_GET['docNo'] ?? '';
    $types = $_GET['types'] ?? '';

    // Query the database for the user
    $sql = "";
    if ($types === 'PAR') {
        $sql = "SELECT
            par.propertyNo AS itemNo,
            par.description AS description,
            par.model AS model,
            par.serialNo AS serialNo,
            par.tagID AS nfcID
        FROM air_items ai
        JOIN par ON par.airNo = ai.air_no
        JOIN users ON users.user_id = ai.enduser_id
        WHERE par.parNo = ? AND par.type = ?";

    } 
    if ($types === 'ICS') {
        $sql = "SELECT
            ics.inventoryNo AS itemNo,
            ics.description AS description,
            ics.model AS model,
            ics.serialNo AS serialNo,
            ics.tagID AS nfcID
        FROM air_items ai
        JOIN ics ON ics.airNo = ai.air_no
        JOIN users ON users.user_id = ai.enduser_id
        WHERE ics.icsNo = ? AND ics.type = ?";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $docNo, $types);
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