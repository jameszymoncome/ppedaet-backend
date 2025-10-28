<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\getDisposed.php

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
                par.tagID,
                par.propertyNo AS itemNo,
                par.description AS description,
                par.model,
                par.serialNo,
                air_items.created_at AS acquisitionDate,
                par.unitCost AS cost,
                air_items.fund AS fund,
                DATE(ih.dateInspected) AS scrapDate,
                CONCAT(users.lastname, ', ', users.firstname, ' ', users.middlename) AS custodian,
                par.unit
            FROM air_items
            JOIN par ON par.airNo = air_items.air_no
            JOIN inspectionhistory ih ON ih.tagID = par.tagID
            JOIN users ON users.user_id = air_items.enduser_id
            WHERE ih.conditions = 'Scrap Condition' AND par.status = 'Disposed'";
    
    if ($role === 'EMPLOYEE') {
        $sql .= " AND users.user_id = ?";
    } elseif ($role === 'ADMIN') {
        $sql .= " AND users.department = ?";
    }

    $sql .= " UNION ALL

            SELECT
                ics.tagID,
                ics.inventoryNo AS itemNo,
                ics.description AS description,
                ics.model,
                ics.serialNo,
                air_items.created_at AS acquisitionDate,
                ics.unitCost AS cost,
                air_items.fund AS fund,
                DATE(ih.dateInspected) AS scrapDate,
                CONCAT(users.lastname, ', ', users.firstname, ' ', users.middlename) AS custodian,
                ics.unit
            FROM air_items
            JOIN ics ON ics.airNo = air_items.air_no
            JOIN inspectionhistory ih ON ih.tagID = ics.tagID
            JOIN users ON users.user_id = air_items.enduser_id
            WHERE ih.conditions = 'Scrap Condition' AND ics.status = 'Disposed'";

    if ($role === 'EMPLOYEE') {
        $sql .= " AND users.user_id = ?";
    } elseif ($role === 'ADMIN') {
        $sql .= " AND users.department = ?";
    }

    $stmt = $conn->prepare($sql);

    if ($role === 'EMPLOYEE') {
        $stmt->bind_param("ii", $usersID, $usersID);
    } elseif ($role === 'ADMIN') {
        $stmt->bind_param("ss", $departments, $departments);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                "tagID" => $row["tagID"],
                "itemNo" => $row["itemNo"],
                "description" => $row["description"],
                "model" => $row["model"],
                "serialNo" => $row["serialNo"],
                "acquisitionDate" => $row["acquisition
nDate"],
                "cost" => $row["cost"],
                "fund" => $row["fund"],
                "dateScrapped" => $row["scrapDate"],
                "custodian" => $row["custodian"],
                "unit" => $row["unit"]

            ];
        }
    }
    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>