<?php
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

    $airNo = $_GET['air_no'] ?? '';
    $type  = strtolower($_GET['type'] ?? ''); // lowercase for safety

    if ($type === 'par') {
        $sql = "
            (
            SELECT 
                ai.air_no,
                par.description AS itemName,
                par.article AS category,
                par.serialNo AS serialno,
                par.tagID AS tagId,
                ih.conditions,
                ih.dateInspected,
                ih.updates,
                ih.remarks,
                ai.created_at,
                'latest' AS recordType
            FROM air_items ai
            LEFT JOIN par ON par.airNo = ai.air_no
            LEFT JOIN inspectionhistory ih 
                ON ih.tagID = par.tagID
            WHERE ai.air_no = ?
              AND par.type = 'PAR'
              AND ih.dateInspected = (
                SELECT MAX(dateInspected)
                FROM inspectionhistory
                WHERE tagID = par.tagID
              )
            )
            UNION ALL
            (
            SELECT 
                ai.air_no,
                par.description AS itemName,
                par.article AS category,
                par.serialNo AS serialno,
                par.tagID AS tagId,
                ih.conditions,
                ih.dateInspected,
                ih.updates,
                ih.remarks,
                ai.created_at,
                'history' AS recordType
            FROM air_items ai
            LEFT JOIN par ON par.airNo = ai.air_no
            LEFT JOIN inspectionhistory ih 
                ON ih.tagID = par.tagID
            WHERE ai.air_no = ?
              AND par.type = 'PAR'
            )
            ORDER BY tagId, dateInspected DESC
        ";
    } elseif ($type === 'ics') {
        $sql = "
            (
            SELECT 
                ai.air_no,
                ics.description AS itemName,
                ics.article AS category,
                ics.serialNo AS serialno,
                ics.tagID AS tagId,
                ih.conditions,
                ih.dateInspected,
                ih.updates,
                ih.remarks,
                ai.created_at,
                'latest' AS recordType
            FROM air_items ai
            LEFT JOIN ics ON ics.airNo = ai.air_no
            LEFT JOIN inspectionhistory ih 
                ON ih.tagID = ics.tagID
            WHERE ai.air_no = ?
              AND ics.type = 'ICS'
              AND ih.dateInspected = (
                SELECT MAX(dateInspected)
                FROM inspectionhistory
                WHERE tagID = ics.tagID
              )
            )
            UNION ALL
            (
            SELECT 
                ai.air_no,
                ics.description AS itemName,
                ics.article AS category,
                ics.serialNo AS serialno,
                ics.tagID AS tagId,
                ih.conditions,
                ih.dateInspected,
                ih.updates,
                ih.remarks,
                ai.created_at,
                'history' AS recordType
            FROM air_items ai
            LEFT JOIN ics ON ics.airNo = ai.air_no
            LEFT JOIN inspectionhistory ih 
                ON ih.tagID = ics.tagID
            WHERE ai.air_no = ?
              AND ics.type = 'ICS'
            )
            ORDER BY tagId, dateInspected DESC
        ";
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Invalid type. Must be 'par' or 'ics'."
        ]);
        exit();
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $airNo, $airNo);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    if (!empty($items)) {
        echo json_encode([
            "success" => true,
            "data" => $items
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "No data found."
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
