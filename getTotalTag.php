<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\getTotalTag.php

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
                SUM(totalTag) AS totalTag,
                SUM(sinceLastYear) AS sinceLastYear
            FROM (
                SELECT 
                    COUNT(*) AS totalTag,
                    SUM(CASE WHEN ai.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) 
                            THEN 1 ELSE 0 END) AS sinceLastYear
                FROM air_items ai
                JOIN par ON par.airNo = ai.air_no
                WHERE par.status = 'Assigned'

                UNION ALL

                SELECT 
                    COUNT(*) AS totalTag,
                    SUM(CASE WHEN ai.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) 
                            THEN 1 ELSE 0 END) AS sinceLastYear
                FROM air_items ai
                JOIN ics ON ics.airNo = ai.air_no
                WHERE ics.status = 'Assigned'
            ) AS combined;

    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            "tagItems" => [
                "totalTag" => $row['totalTag'],
                "sinceLastYear" => $row['sinceLastYear']
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