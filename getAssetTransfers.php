<?php
// filepath: c:\xampp\htdocs\ppedaet-backend\getAssetTransfers.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once 'db_connection.php';

$database = new Database();
$conn = $database->conn;

// ✅ Fetch all transfers with optional aggregated items
$sql = "
    SELECT
        t.id AS transfer_id,
        t.ptr_no, 
        t.entity_name, 
        CONCAT(u_to.firstname, ' ', u_to.lastname) AS to_officer_name, 
        CONCAT(u_approved.firstname, ' ', u_approved.lastname) AS approved_by_name, 
        t.transfer_type,
        t.signed_doc AS signed_doc, 
        t.reason_for_transfer, 
        CONCAT(u_from.firstname, ' ', u_from.lastname) AS from_officer_name,
        t.from_officer AS from_officerID, 
        t.to_officer AS to_officerID, 
        u_to.department,
        u_to.role as to_officer_role,
        u_from.role as from_officer_role, 
        t.transfer_date, 
        t.status
    FROM asset_transfer AS t
    LEFT JOIN users AS u_to
        ON u_to.user_id = t.to_officer
    LEFT JOIN users AS u_from
        ON u_from.user_id = t.from_officer
    LEFT JOIN users AS u_approved
        ON u_approved.user_id = t.approved_by
    ORDER BY t.transfer_date DESC
";

$result = $conn->query($sql);

$transfers = [];

while ($row = $result->fetch_assoc()) {
    $ptrNo = $row['ptr_no'];

    // ✅ Fetch related items for this transfer
    $itemSql = "
        SELECT 
            at.quantity, 
            at.unit, 
            at.description, 
            at.propertyNo, 
            at.amount, 
            at.remarks
        FROM asset_transfer_items AS at
        LEFT JOIN asset_transfer AS a 
            ON a.id = at.transfer_id
        WHERE a.ptr_no = ?
    ";

    $stmt = $conn->prepare($itemSql);
    $stmt->bind_param("s", $ptrNo);
    $stmt->execute();
    $itemResult = $stmt->get_result();

    $items = [];
    while ($itemRow = $itemResult->fetch_assoc()) {
        $items[] = $itemRow;
    }
    $stmt->close();

    // ✅ Attach items
    $row['items'] = $items;

    $transfers[] = $row;
}

echo json_encode(["transfers" => $transfers], JSON_PRETTY_PRINT);
$conn->close();
