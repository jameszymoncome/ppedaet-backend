<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\getReportIssue.php

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

    $sql = "WITH latest_updates AS (
                SELECT
                    ih.tagID,
                    MAX(ih.dateInspected) AS latestDate
                FROM inspectionhistory ih
                GROUP BY ih.tagID
            ),

            all_filtered AS (
                SELECT
                    par.tagID,
                    par.propertyNo AS docNo,
                    par.description,
                    par.model,
                    par.serialNo,
                    par.type,
                    users.department,
                    ih.updates,
                    ih.dateInspected
                FROM air_items
                JOIN par ON par.airNo = air_items.air_no
                JOIN inspectionhistory ih ON ih.tagID = par.tagID
                JOIN latest_updates lu ON lu.tagID = ih.tagID
                JOIN users ON users.user_id = air_items.enduser_id
                WHERE (SELECT updates 
                        FROM inspectionhistory 
                        WHERE tagID = ih.tagID AND dateInspected = lu.latestDate) <> 'Repaired'
                AND ih.updates <> ''
                AND par.status <> 'Disposed'";

    if ($role === 'EMPLOYEE') {
        $sql .= " AND users.user_id = ?";
    } elseif ($role === 'ADMIN') {
        $sql .= " AND users.department = ?";
    }

    $sql .= " UNION ALL
                SELECT
                    ics.tagID,
                    ics.inventoryNo AS docNo,
                    ics.description,
                    ics.model,
                    ics.serialNo,
                    ics.type,
                    users.department,
                    ih.updates,
                    ih.dateInspected
                FROM air_items
                JOIN ics ON ics.airNo = air_items.air_no
                JOIN inspectionhistory ih ON ih.tagID = ics.tagID
                JOIN latest_updates lu ON lu.tagID = ih.tagID
                JOIN users ON users.user_id = air_items.enduser_id
                WHERE (SELECT updates 
                        FROM inspectionhistory 
                        WHERE tagID = ih.tagID AND dateInspected = lu.latestDate) <> 'Repaired'
                AND ih.updates <> ''
                AND ics.status <> 'Disposed'";

    if ($role === 'EMPLOYEE') {
        $sql .= " AND users.user_id = ?";
    } elseif ($role === 'ADMIN') {
        $sql .= " AND users.department = ?";
    }

    $sql .= "),
            ranked AS (
                SELECT *,
                    ROW_NUMBER() OVER (PARTITION BY tagID ORDER BY dateInspected DESC) AS rn
                FROM all_filtered
            )
            SELECT tagID, docNo AS itemNo, description, model, serialNo, type, department, updates
            FROM ranked
            WHERE rn = 1;";

    // ✅ Prepare the query
    $stmt = $conn->prepare($sql);

    // ✅ Bind both placeholders (one for each part of the UNION)
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
                "type" => $row["type"],
                "department" => $row["department"],
                "conditions" => $row["updates"]

            ];
        }
    }
    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>