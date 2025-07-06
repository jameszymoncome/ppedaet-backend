<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\getSupplier.php

error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connection.php';

try {
    // Get database connection
    $conn = getDatabaseConnection();

    $q = $_GET['q'] ?? '';


    // Query the database for the user
    $sql = "SELECT supplierID as id, supplierName as name, supplierAddress as address, tinNumber as tin FROM supplierinfo WHERE supplierName LIKE CONCAT('%', ?, '%') LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $q);
    $stmt->execute();
    $result = $stmt->get_result();

    $suppliers = [];

    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "No suppliers found"]);
        exit();
    }

    while ($row = $result->fetch_assoc()) {
        $suppliers[] = $row;
    }

    echo json_encode($suppliers);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>