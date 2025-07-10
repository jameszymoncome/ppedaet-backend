<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\getSupplier.php

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

    $q = $_GET['article'] ?? '';
    $searchTerm = "%" . $q . "%";


    // Query the database for the user
    $sql = "SELECT categoryID, categoryName FROM categorycode WHERE categoryID LIKE ? OR categoryName LIKE ? LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    $articles = [];
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }

    echo json_encode($articles);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>