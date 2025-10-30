<?php
// filepath: c:\xampp\htdocs\ppedaet-backend\em_getAssetTransferItems.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once 'db_connection.php';

$database = new Database();
$conn = $database->conn;

$user_id = $_GET['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(["error" => "Missing user_id"]);
    exit;
}

/*
  For each asset_transfer_items (ati) row:
  - atf.type = 'PAR'  => use par where propertyNo = ati.propertyNo
  - atf.type = 'ICS'  => use ics where inventoryNo = ati.propertyNo
  - atf.type = 'PTR'  => find latest assets row for this item_no where type='PTR'
                        read a_orig.origin_type then join that origin (par/ics) by item_no
*/

$sql = "
SELECT
  ati.id AS ati_id,
  ati.quantity,
  -- unit is taken from original source tables (PAR/ICS) where possible
  COALESCE(
    -- when transfer header says PAR
    (CASE WHEN atf.type = 'PAR' THEN p.unit END),
    -- when transfer header says ICS
    (CASE WHEN atf.type = 'ICS' THEN i.unit END),
    -- when transfer header says PTR: use the origin table (p2 / i2) discovered from latest PTR asset
    p2.unit,
    i2.unit,
    ati.unit         -- fallback to the unit recorded on the transfer item itself
  ) AS unit,
  ati.description AS transfer_description,
  ati.propertyNo,
  -- amount: prefer item-specific then fall back to par/ics unitCost
  COALESCE(
    ati.amount,
    (CASE WHEN atf.type = 'PAR' THEN p.unitCost END),
    (CASE WHEN atf.type = 'ICS' THEN i.unitCost END),
    p2.unitCost,
    i2.unitCost
  ) AS amount,
  ati.remarks,
  atf.ptr_no,
  atf.type AS transfer_type,
  atf.from_officer AS from_officerID,
  CONCAT(u_from.firstname, ' ', u_from.lastname) AS from_officer_name,

  -- final resolved source fields for frontend convenience
  COALESCE(
    (CASE WHEN atf.type = 'PAR' THEN p.article END),
    (CASE WHEN atf.type = 'ICS' THEN i.article END),
    p2.article,
    i2.article
  ) AS source_article,

  COALESCE(
    (CASE WHEN atf.type = 'PAR' THEN p.parNo END),
    (CASE WHEN atf.type = 'ICS' THEN i.icsNo END),
    p2.parNo,
    i2.icsNo
  ) AS source_doc_no,

  -- resolved origin type (PAR/ICS/PTR)
  COALESCE(
    atf.type,
    a_orig.origin_type,
    'UNKNOWN'
  ) AS resolved_source_type

FROM asset_transfer_items ati
JOIN asset_transfer atf ON atf.id = ati.transfer_id
LEFT JOIN users u_from ON u_from.user_id = atf.from_officer

-- direct joins for when the transfer header explicitly indicates PAR / ICS
LEFT JOIN par p ON atf.type = 'PAR' AND TRIM(ati.propertyNo) = TRIM(p.propertyNo)
LEFT JOIN ics i ON atf.type = 'ICS' AND TRIM(ati.propertyNo) = TRIM(i.inventoryNo)

-- find the latest assets row (type='PTR') for this propertyNo if exists
LEFT JOIN (
  SELECT a1.item_no, a1.origin_type, a1.origin_user_id, a1.date_acquired, a1.id
  FROM assets a1
  INNER JOIN (
    SELECT item_no, MAX(id) AS max_id
    FROM assets
    WHERE type = 'PTR'
    GROUP BY item_no
  ) latest ON a1.item_no = latest.item_no AND a1.id = latest.max_id
) a_orig ON TRIM(a_orig.item_no) = TRIM(ati.propertyNo)

-- using the a_orig.origin_type, attempt to fetch the original PAR/ICS rows
LEFT JOIN par p2 ON a_orig.origin_type = 'PAR' AND TRIM(a_orig.item_no) = TRIM(p2.propertyNo)
LEFT JOIN ics i2 ON a_orig.origin_type = 'ICS' AND TRIM(a_orig.item_no) = TRIM(i2.inventoryNo)

WHERE atf.from_officer = ?
ORDER BY ati.id ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["error" => "Prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = [
        "id" => (int)$row['ati_id'],
        "quantity" => $row['quantity'],
        "unit" => $row['unit'],
        "description" => $row['transfer_description'],
        "propertyNo" => $row['propertyNo'],
        "amount" => $row['amount'],
        "remarks" => $row['remarks'],
        "ptr_no" => $row['ptr_no'],
        "transfer_type" => $row['transfer_type'],
        "from_officerID" => $row['from_officerID'],
        "from_officer_name" => $row['from_officer_name'],
        "source_article" => $row['source_article'],
        "source_doc_no" => $row['source_doc_no'],
        "resolved_source_type" => $row['resolved_source_type']
    ];
}

echo json_encode(["items" => $items], JSON_PRETTY_PRINT);

$stmt->close();
$conn->close();
