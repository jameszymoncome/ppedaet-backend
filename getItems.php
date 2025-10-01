<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\getItems.php
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
    $conn = getDatabaseConnection();

    $sql = "SELECT *
            FROM (
                SELECT
                    ai.id,
                    ai.air_no,
                    par.parNo AS documentNo,
                    par.type AS types,
                    CONCAT(u.firstname, ' ', u.middlename, ' ', u.lastname) AS endUser,
                    u.department,
                    DATE_FORMAT(ai.created_at, '%m-%d-%Y') AS issued_date,
                    COUNT(*) AS item_count,
                    CASE
                        WHEN SUM(CASE 
                                    WHEN COALESCE(par.status) = 'Assigned' THEN 1 
                                    ELSE 0 
                                END) > 0 THEN 'Assigned'
                        WHEN SUM(CASE 
                                    WHEN COALESCE(par.status) = 'For Tagging' THEN 1 
                                    ELSE 0 
                                END) > 0 THEN 'For Tagging'
                        WHEN SUM(CASE 
                                    WHEN COALESCE(par.status) = 'Done Tagging' THEN 1 
                                    ELSE 0 
                                END) = COUNT(*) THEN 'Upload Scanned Copy'
                        ELSE MAX(COALESCE(par.status))
                    END AS status,
                    ai.created_at
                FROM air_items ai
                JOIN par ON par.airNo = ai.air_no
                JOIN users u ON u.user_id = ai.enduser_id
                GROUP BY ai.air_no, ai.fund, ai.air_date, documentNo, types

                UNION ALL

                SELECT
                    ai.id,
                    ai.air_no,
                    ics.icsNo AS documentNo,
                    ics.type AS types,
                    CONCAT(u.firstname, ' ', u.middlename, ' ', u.lastname) AS endUser,
                    u.department,
                    DATE_FORMAT(ai.created_at, '%m-%d-%Y') AS issued_date,
                    COUNT(*) AS item_count,
                    CASE
                        WHEN SUM(CASE 
                                    WHEN COALESCE(ics.status) = 'Assigned' THEN 1 
                                    ELSE 0 
                                END) > 0 THEN 'Assigned'
                        WHEN SUM(CASE 
                                    WHEN COALESCE(ics.status) = 'For Tagging' THEN 1 
                                    ELSE 0 
                                END) > 0 THEN 'For Tagging'
                        WHEN SUM(CASE 
                                    WHEN COALESCE(ics.status) = 'Done Tagging' THEN 1 
                                    ELSE 0 
                                END) = COUNT(*) THEN 'Upload Scanned Copy'
                        ELSE MAX(COALESCE(ics.status))
                    END AS status,
                    ai.created_at
                FROM air_items ai
                JOIN ics ON ics.airNo = ai.air_no
                JOIN users u ON u.user_id = ai.enduser_id
                GROUP BY ai.air_no, ai.fund, ai.air_date, documentNo, types
            ) AS combined
            ORDER BY combined.created_at DESC;
            ";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];

    while ($row = $result->fetch_assoc()) {
        $items[] = [
            "id" => $row['id'],
            "air_no" => $row['air_no'],
            "documentNo" => $row['documentNo'],
            "type" => $row['types'],
            "user" => trim(preg_replace('/\s+/', ' ', $row['endUser'])), // remove double spaces if middlename is empty
            "office" => $row['department'],
            "dateIssued" => $row['issued_date'],
            "items" => (int)$row['item_count'],
            "status" => $row['status'],
            "created_at" => $row['created_at']
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
