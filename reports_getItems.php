<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\reports_getItems.php
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

    // ✅ Handle both PAR and ICS records in a UNION for cleaner logic
    $sql = "
        SELECT
            ai.id,
            ai.air_no,
            par.parNo AS documentNo,
            par.type AS types,
            CONCAT(u.firstname, ' ', COALESCE(u.middlename, ''), ' ', u.lastname) AS endUser,
            u.department,
            DATE_FORMAT(ai.created_at, '%m-%d-%Y') AS issued_date,
            COUNT(ai.id) AS item_count,
            CASE
                WHEN SUM(par.status = 'Assigned') > 0 THEN 'Assigned'
                WHEN SUM(par.status = 'For Tagging') > 0 THEN 'For Tagging'
                WHEN SUM(par.status = 'Done Tagging') = COUNT(ai.id) THEN 'Upload Scanned Copy'
                ELSE MAX(par.status)
            END AS status
        FROM air_items ai
        LEFT JOIN par ON par.airNo = ai.air_no
        LEFT JOIN users u ON u.user_id = ai.enduser_id
        WHERE par.parNo IS NOT NULL
        GROUP BY ai.air_no, documentNo, types, endUser, u.department, issued_date

        UNION ALL

        SELECT
            ai.id,
            ai.air_no,
            ics.icsNo AS documentNo,
            ics.type AS types,
            CONCAT(u.firstname, ' ', COALESCE(u.middlename, ''), ' ', u.lastname) AS endUser,
            u.department,
            DATE_FORMAT(ai.created_at, '%m-%d-%Y') AS issued_date,
            COUNT(ai.id) AS item_count,
            CASE
                WHEN SUM(ics.status = 'Assigned') > 0 THEN 'Assigned'
                WHEN SUM(ics.status = 'For Tagging') > 0 THEN 'For Tagging'
                WHEN SUM(ics.status = 'Done Tagging') = COUNT(ai.id) THEN 'Upload Scanned Copy'
                ELSE MAX(ics.status)
            END AS status
        FROM air_items ai
        LEFT JOIN ics ON ics.airNo = ai.air_no
        LEFT JOIN users u ON u.user_id = ai.enduser_id
        WHERE ics.icsNo IS NOT NULL
        GROUP BY ai.air_no, documentNo, types, endUser, u.department, issued_date
    ";

    $sql = "
        SELECT * FROM (
            $sql
        ) AS combined
        WHERE status = 'Assigned'
        ORDER BY issued_date DESC
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
            "user" => trim(preg_replace('/\s+/', ' ', $row['endUser'])),
            "office" => $row['department'],
            "dateIssued" => $row['issued_date'],
            "items" => (int)$row['item_count'],
            "status" => $row['status']
        ];
    }

    echo json_encode([
        "success" => !empty($items),
        "items" => $items,
        "message" => empty($items) ? "No 'Assigned' data found" : null
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>
