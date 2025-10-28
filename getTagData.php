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
    $database = new Database();
    $conn = $database->conn;

    $role = $_GET['role'] ?? '';
    $usersID = $_GET['usersID'] ?? '';
    $departments = $_GET['departments'] ?? '';

    $sql = "SELECT
                COUNT(DISTINCT CONCAT(tagID, '-', YEAR(dateInspected))) AS inspectionCount,
                DATE_FORMAT(MAX(dateInspected), '%M %d, %Y') AS formatted_date
            FROM (
                SELECT ih.tagID, ih.dateInspected
                FROM air_items
                LEFT JOIN par ON par.airNo = air_items.air_no
                LEFT JOIN inspectionhistory ih ON ih.tagID = par.tagID
                INNER JOIN users ON users.user_id = air_items.enduser_id";
    
    if ($role === 'EMPLOYEE') {
        $sql .= " AND users.user_id = ?";
    } elseif ($role === 'ADMIN') {
        $sql .= " AND users.department = ?";
    }

    $sql .= " UNION ALL

                SELECT ih.tagID, ih.dateInspected
                FROM air_items
                LEFT JOIN ics ON ics.airNo = air_items.air_no
                LEFT JOIN inspectionhistory ih ON ih.tagID = ics.tagID
                INNER JOIN users ON users.user_id = air_items.enduser_id";
    if ($role === 'EMPLOYEE') {
        $sql .= " AND users.user_id = ?";
    } elseif ($role === 'ADMIN') {
        $sql .= " AND users.department = ?";
    }

    $sql .= " ) AS combined;";

    $stmt = $conn->prepare($sql);

    if ($role === 'EMPLOYEE') {
        $stmt->bind_param("ii", $usersID, $usersID);
    } elseif ($role === 'ADMIN') {
        $stmt->bind_param("ss", $departments, $departments);
    }

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