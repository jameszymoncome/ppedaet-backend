<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\getInspects.php
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

    $role = $_GET['role'] ?? '';
    $usersID = $_GET['usersID'] ?? '';
    $departments = $_GET['departments'] ?? '';

    $sql = "SELECT
                par.tagID AS tagID,
                par.propertyNo AS docNo,
                par.description AS description,
                par.model AS model,
                par.serialNo AS serialNo,
                users.department,
                CONCAT(users.firstname, ' ', users.middlename, ' ', users.lastname) AS assignedTo,
                Date(inspectionhistory.dateInspected) AS dateInspected,
                inspectionhistory.conditions,
                inspectionhistory.remarks
            FROM air_items
            LEFT JOIN par ON par.airNo = air_items.air_no
            INNER JOIN users ON users.user_id = air_items.enduser_id
            LEFT JOIN inspectionhistory ON inspectionhistory.tagID = par.tagID
            WHERE inspectionhistory.dateInspected IS NOT NULL 
            AND inspectionhistory.conditions <> '' 
            AND YEAR(inspectionhistory.dateInspected) = YEAR(CURDATE()) ";
    
    if ($role === 'EMPLOYEE') {
        $sql .= " AND users.user_id = ?";
    } elseif ($role === 'ADMIN') {
        $sql .= " AND users.department = ?";
    }

    $sql .= " UNION ALL

            SELECT
                ics.tagID AS tagID,
                ics.inventoryNo AS docNo,
                ics.description AS description,
                ics.model AS model,
                ics.serialNo AS serialNo,
                users.department,
                CONCAT(users.firstname, ' ', users.middlename, ' ', users.lastname) AS assignedTo,
                DATE(inspectionhistory.dateInspected) AS dateInspected,
                inspectionhistory.conditions,
                inspectionhistory.remarks
            FROM air_items
            LEFT JOIN ics ON ics.airNo = air_items.air_no
            INNER JOIN users ON users.user_id = air_items.enduser_id
            LEFT JOIN inspectionhistory ON inspectionhistory.tagID = ics.tagID
            WHERE inspectionhistory.dateInspected IS NOT NULL 
            AND inspectionhistory.conditions <> '' 
            AND YEAR(inspectionhistory.dateInspected) = YEAR(CURDATE())";
    
    if ($role === 'EMPLOYEE') {
        $sql .= " AND users.user_id = ?";
    } elseif ($role === 'ADMIN') {
        $sql .= " AND users.department = ?";
    }

    $sql .= " ORDER BY dateInspected DESC;

            ";
    $stmt = $conn->prepare($sql);
    if ($role === 'EMPLOYEE') {
        $stmt->bind_param("ii", $usersID, $usersID);
    } elseif ($role === 'ADMIN') {
        $stmt->bind_param("ss", $departments, $departments);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];

    $id = 1;

    while ($row = $result->fetch_assoc()) {
        $items[] = [
            "id" => $id++,
            "tagID" => $row['tagID'],
            "docNo" => $row['docNo'],
            "description" => $row['description'],
            "model" => $row['model'],
            "serialNo" => $row['serialNo'],
            "department" => $row['department'],
            "assignedTo" => $row['assignedTo'],
            "dateInspected" => $row['dateInspected'],
            "conditions" => $row['conditions'],
            "remarks" => $row['remarks']
        ];
    }

    if (count($items) > 0) {
        echo json_encode(["items" => $items]);
    } else {
        echo json_encode(["success" => false, "message" => "No data found"]);
    }

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
