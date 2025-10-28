<?php
// filepath: c:\xampp\htdocs\ppedaet-backend\getAssets.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once 'db_connection.php';

$user_id = $_GET['user_id'] ?? 0;
$database = new Database();
$conn = $database->conn;

/*
  ✅ Modified Unified Query Logic:
    1. Define a subquery to find all item_nos that have a completed PTR record.
    2. Exclude these item_nos from the PAR and ICS unions.
    3. Enhance the PTR union to correctly fetch item details from original tables.
*/

$sql = "
-- Find all item numbers that have a PTR entry (meaning they have been transferred)
WITH TransferredAssets AS (
    SELECT item_no FROM assets WHERE type = 'PTR'
)

-- 🧾 PAR Records (Only for items NOT transferred)
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
LEFT JOIN assets a ON a.item_no = p.propertyNo AND a.type = 'PAR'
LEFT JOIN users u ON a.current_user_id = u.user_id
WHERE p.status = 'Assigned'
AND p.propertyNo NOT IN (SELECT item_no FROM TransferredAssets) -- 🛑 EXCLUDE Transferred Items

UNION ALL

-- 🧾 ICS Records (Only for items NOT transferred)
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
LEFT JOIN assets a ON a.item_no = i.inventoryNo AND a.type = 'ICS'
LEFT JOIN users u ON a.current_user_id = u.user_id
WHERE i.status = 'Assigned'
AND i.inventoryNo NOT IN (SELECT item_no FROM TransferredAssets) -- 🛑 EXCLUDE Transferred Items

UNION ALL

-- 🧾 PTR (Transferred Assets) - Only show the latest record (PTR)
SELECT DISTINCT
    a.item_no AS itemNo,
    CONCAT(u.firstname, ' ', u.lastname) AS assigned_to,
    u.user_id AS user_id,
    u.department,
    -- 🔑 Fetch details from original tables based on origin_type
    COALESCE(p_orig.article, i_orig.article) AS article,
    COALESCE(p_orig.description, i_orig.description) AS description,
    COALESCE(p_orig.model, i_orig.model) AS model,
    COALESCE(p_orig.serialNo, i_orig.serialNo) AS serial_no,
    CONCAT('PTR (from ', a.origin_type, ')') AS document_type,
    atf.ptr_no AS document_no,
    'Assigned' AS status,
    a.date_acquired
FROM assets a
JOIN asset_transfer_items ati 
    ON a.item_no = ati.propertyNo
JOIN asset_transfer atf 
    ON ati.transfer_id = atf.id
LEFT JOIN users u 
    ON a.current_user_id = u.user_id
-- 🔑 LEFT JOIN to original PAR and ICS tables
LEFT JOIN par p_orig 
    ON a.item_no = p_orig.propertyNo AND a.origin_type = 'PAR'
LEFT JOIN ics i_orig 
    ON a.item_no = i_orig.inventoryNo AND a.origin_type = 'ICS'
WHERE a.type = 'PTR' 
    AND atf.status = 'Completed'
ORDER BY date_acquired DESC
";

$result = $conn->query($sql);

$assets = [];
while ($row = $result->fetch_assoc()) {
    $assets[] = $row;
}

echo json_encode(["assets" => $assets]);
$conn->close();
?>