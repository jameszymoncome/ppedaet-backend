<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\getTagData.php

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

    $sql = "SELECT 
                COUNT(DISTINCT CONCAT(ih.tagID, '-', YEAR(ih.dateInspected))) AS inspectionCount,
                DATE_FORMAT(MAX(ih.dateInspected), '%M %d, %Y') AS formatted_date
            FROM inspectionhistory ih;
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            "tagItems" => [
                "inspectionCount" => $row['inspectionCount'],
                "formatted_date" => $row['formatted_date']
            ]
        ]);
    } else {
        echo json_encode([
            "success" => true,
            "tagItems" => [
                "totalTag" => 0,
                "sinceLastYear" => 0
            ]
        ]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>