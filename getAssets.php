<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once 'db_connection.php';

$database = new Database();
$conn = $database->conn;

$sql = "
/* PAR (not yet transferred or only pending transfer) */
SELECT
    p.propertyNo AS itemNo,
    CONCAT(u.firstname, ' ', u.lastname) AS assigned_to,
    u.user_id AS user_id,
    u.department,
    p.article,
    p.description,
    p.model,
    p.serialNo AS serial_no,
    'PAR' AS document_type,
    p.parNo AS document_no,
    p.status,
    a.date_acquired
FROM par p
LEFT JOIN assets a 
    ON a.item_no = p.propertyNo AND a.type = 'PAR'
LEFT JOIN users u 
    ON a.current_user_id = u.user_id
WHERE p.status = 'Assigned'
  AND p.propertyNo NOT IN (
      SELECT ati.propertyNo
      FROM asset_transfer_items ati
      JOIN asset_transfer atf ON ati.transfer_id = atf.id
      WHERE atf.status = 'Completed' AND atf.type = 'PAR'
  )

UNION ALL

/* ICS (not yet transferred or only pending transfer) */
SELECT
    i.inventoryNo AS itemNo,
    CONCAT(u.firstname, ' ', u.lastname) AS assigned_to,
    u.user_id AS user_id,
    u.department,
    i.article,
    i.description,
    i.model,
    i.serialNo AS serial_no,
    'ICS' AS document_type,
    i.icsNo AS document_no,
    i.status,
    a.date_acquired
FROM ics i
LEFT JOIN assets a 
    ON a.item_no = i.inventoryNo AND a.type = 'ICS'
LEFT JOIN users u 
    ON a.current_user_id = u.user_id
WHERE i.status = 'Assigned'
  AND i.inventoryNo NOT IN (
      SELECT ati.propertyNo
      FROM asset_transfer_items ati
      JOIN asset_transfer atf ON ati.transfer_id = atf.id
      WHERE atf.status = 'Completed' AND atf.type = 'ICS'
  )

UNION ALL

/* PTR (show completed transfers with origin details) */
SELECT
    ati.propertyNo AS itemNo,
    CONCAT(u_to.firstname, ' ', u_to.lastname) AS assigned_to,
    u_to.user_id AS user_id,
    u_to.department AS department,
    COALESCE(p.article, i.article) AS article,
    COALESCE(p.description, i.description) AS description,
    COALESCE(p.model, i.model) AS model,
    COALESCE(p.serialNo, i.serialNo) AS serial_no,
    CONCAT('PTR (from ', atf.type, ')') AS document_type,
    atf.ptr_no AS document_no,
    'Assigned' AS status,
    atf.transfer_date AS date_acquired
FROM asset_transfer atf
JOIN asset_transfer_items ati ON ati.transfer_id = atf.id
LEFT JOIN users u_to ON atf.to_officer = u_to.user_id
LEFT JOIN par p 
    ON atf.type = 'PAR' AND TRIM(ati.propertyNo) = TRIM(p.propertyNo)
LEFT JOIN ics i 
    ON atf.type = 'ICS' AND TRIM(ati.propertyNo) = TRIM(i.inventoryNo)
WHERE atf.status = 'Completed'

ORDER BY date_acquired DESC
";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(["success" => false, "message" => "Query error: " . $conn->error]);
    $conn->close();
    exit;
}

$assets = [];
while ($row = $result->fetch_assoc()) {
    $assets[] = $row;
}

echo json_encode(["success" => true, "assets" => $assets]);
$conn->close();
?>
