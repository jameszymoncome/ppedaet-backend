<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\EM_getAssets.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once 'db_connection.php';

$database = new Database();
$conn = $database->conn;

// ✅ Get logged-in user's ID
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["success" => false, "message" => "Missing user_id"]);
    exit;
}

// ✅ Unified query — show assets only for the given user_id
$sql = "
/* PAR (not transferred or only pending transfer) */
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
  AND u.user_id = ?
  AND NOT EXISTS (
      SELECT 1
      FROM assets a2
      JOIN asset_transfer_items ati2 
          ON a2.item_no = ati2.propertyNo
      JOIN asset_transfer atf2 
          ON ati2.transfer_id = atf2.id
      WHERE TRIM(a2.item_no) = TRIM(p.propertyNo)
        AND a2.origin_type = 'PAR'
        AND a2.type = 'PTR'
        AND atf2.status = 'Completed'
  )

UNION ALL

/* ICS (not transferred or only pending transfer) */
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
  AND u.user_id = ?
  AND NOT EXISTS (
      SELECT 1
      FROM assets a2
      JOIN asset_transfer_items ati2 
          ON a2.item_no = ati2.propertyNo
      JOIN asset_transfer atf2 
          ON ati2.transfer_id = atf2.id
      WHERE TRIM(a2.item_no) = TRIM(i.inventoryNo)
        AND a2.origin_type = 'ICS'
        AND a2.type = 'PTR'
        AND atf2.status = 'Completed'
  )

UNION ALL

/* PTR (show completed transfers with full details) */
SELECT
    ati.propertyNo AS itemNo,
    CONCAT(u_to.firstname, ' ', u_to.lastname) AS assigned_to,
    u_to.user_id AS user_id,
    u_to.department AS department,
    COALESCE(p.article, i.article, 'Transferred Asset') AS article,
    COALESCE(p.description, i.description, NULL) AS description,
    COALESCE(p.model, i.model, NULL) AS model,
    COALESCE(p.serialNo, i.serialNo, NULL) AS serial_no,
    CONCAT('PTR (from ', atf.type, ')') AS document_type,
    atf.ptr_no AS document_no,
    'Assigned' AS status,
    atf.transfer_date AS date_acquired
FROM asset_transfer atf
JOIN asset_transfer_items ati
    ON ati.transfer_id = atf.id
LEFT JOIN users u_to
    ON atf.to_officer = u_to.user_id
LEFT JOIN par p
    ON atf.type = 'PAR' AND TRIM(ati.propertyNo) = TRIM(p.propertyNo)
LEFT JOIN ics i
    ON atf.type = 'ICS' AND TRIM(ati.propertyNo) = TRIM(i.inventoryNo)
WHERE atf.status = 'Completed'
  AND u_to.user_id = ?

/* ✅ Apply ORDER BY to the entire UNION result */
ORDER BY date_acquired DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

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
