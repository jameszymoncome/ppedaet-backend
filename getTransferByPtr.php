<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connection.php';
$database = new Database();
$conn = $database->conn;

// ✅ Get PTR number
$ptr_no = $_GET['ptr_no'] ?? '';
$type = $_GET['type'] ?? '';

if (!$ptr_no) {
    echo json_encode(["success" => false, "message" => "Missing ptr_no"]);
    exit;
}

// ✅ Fetch transfer info + items
$sql = "
    SELECT 
        atf.ptr_no,
        atf.from_officer,
        CONCAT(u_from.firstname, ' ', u_from.lastname) AS from_officer_name,
        atf.to_officer,
        CONCAT(u_to.firstname, ' ', u_to.lastname) AS to_officer_name,
        CONCAT(u_approver.firstname, ' ', u_approver.lastname) AS approver_name,
        atf.status,
        atf.type,
        atf.transfer_date,
        atf.signed_doc,
        atf.entity_name,
        atf.transfer_type,
        atf.reason_for_transfer,
        ati.propertyNo AS item_no,
        a.type AS current_type,
        a.origin_type,
        a.current_user_id,
        ati.quantity,
        ati.remarks,
        ati.unit,
        ati.description,
        ati.propertyNo,
        ati.amount
    FROM asset_transfer atf
    JOIN asset_transfer_items ati ON atf.id = ati.transfer_id
    LEFT JOIN assets a ON ati.propertyNo = a.item_no
    LEFT JOIN users AS u_from ON atf.from_officer = u_from.user_id
    LEFT JOIN users AS u_to ON atf.to_officer = u_to.user_id
    LEFT JOIN users AS u_approver ON atf.approved_by = u_approver.user_id
    WHERE atf.ptr_no = ? AND atf.type = ?;
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $ptr_no, $type);
$stmt->execute();
$result = $stmt->get_result();

$transfer = [];
$items = [];

while ($row = $result->fetch_assoc()) {
    if (empty($transfer)) {
        $transfer = [
            "ptr_no" => $row['ptr_no'],
            "from_officer" => $row['from_officer'],
            "from_officer_name" => $row['from_officer_name'],
            "to_officer" => $row['to_officer'],
            "to_officer_name" => $row['to_officer_name'],
            "approver_name" => $row['approver_name'],
            "status" => $row['status'],
            "reason_for_transfer" => $row['reason_for_transfer'],
            "type" => $row['type'],
            "transfer_date" => $row['transfer_date'],
            "signed_doc" => $row['signed_doc'],
            "entity_name" => $row['entity_name'],
            "transfer_type" => $row['transfer_type'],
            "items" => []
        ];
    }

    $items[] = [
        "item_no" => $row['item_no'], 
        "current_type" => $row['current_type'],
        "origin_type" => $row['origin_type'],
        "current_user_id" => $row['current_user_id'],
        "unit" => $row['unit'],
        "quantity" => $row['quantity'],
        "remarks" => $row['remarks'],
        "description" => $row['description'],
        "propertyNo" => $row['propertyNo'],
        "amount" => $row['amount']
    ];
}

$stmt->close();

if (empty($transfer)) {
    echo json_encode(["success" => false, "message" => "No transfer found for this PTR"]);
    exit;
}

$transfer['items'] = $items;

echo json_encode(["success" => true, "transfer" => $transfer]);
?>
