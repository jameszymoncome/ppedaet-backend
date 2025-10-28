<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connection.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['transferForm']) || !isset($data['assets'])) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

$database = new Database();
$conn = $database->conn;
$conn->begin_transaction();

try {
    /**
     * ✅ Determine type (PAR or ICS)
     */
    $totalAmount = 0;
    foreach ($data['assets'] as $asset) {
        $amount = (float)($asset['unitCost'] ?? 0);
        $quantity = (int)($asset['quantity'] ?? 1);
        $totalAmount += $amount * $quantity;
    }
    $type = ($totalAmount >= 50000) ? "PAR" : "ICS";

    /**
     * 1. Insert into asset_transfer
     */
    $transferSql = "INSERT INTO asset_transfer (
        ptr_no, entity_name, from_officer, to_officer, transfer_type, reason_for_transfer,
        approved_by, released_by, received_by, transfer_date, status, type, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($transferSql);

    $ptr_no              = $data['transferForm']['ptr_no'];
    $entity_name         = $data['transferForm']['entity_name'];
    $from_officer        = $data['transferForm']['from_officer'];
    $to_officer          = $data['transferForm']['to_officer'];
    $transfer_type       = $data['transferForm']['transfer_type'];
    $reason_for_transfer = $data['transferForm']['reason_for_transfer'];
    $approved_by         = $data['transferForm']['approved_by'];
    $released_by         = $data['transferForm']['released_by'];
    $received_by         = $data['transferForm']['received_by'];
    $transfer_date       = $data['transferForm']['transfer_date'];
    $status              = $data['transferForm']['status'] ?? 'Pending';

    $stmt->bind_param(
        "ssssssssssss",
        $ptr_no,
        $entity_name,
        $from_officer,
        $to_officer,
        $transfer_type,
        $reason_for_transfer,
        $approved_by,
        $released_by,
        $received_by,
        $transfer_date,
        $status,
        $type
    );
    $stmt->execute();
    $transferId = $conn->insert_id;
    $stmt->close();

    /**
     * 2. Insert asset_transfer_items
     */
    $itemSql = "INSERT INTO asset_transfer_items 
        (transfer_id, article, description, propertyNo, unit, quantity, amount, remarks) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            quantity = VALUES(quantity),
            remarks = VALUES(remarks)";

    $stmt = $conn->prepare($itemSql);

    foreach ($data['assets'] as $asset) {
        $article     = $asset['article'] ?? '';
        $description = $asset['description'] ?? '';
        $property_no = $asset['propertyNo'] ?? $asset['property_no'] ?? '';
        $unit        = $asset['unit'] ?? '';
        $quantity    = (int)($asset['quantity'] ?? 0);
        $amount      = (float)($asset['unitCost'] ?? 0);
        $remarks     = $asset['remarks'] ?? '';

        $stmt->bind_param(
            "issssids",
            $transferId,
            $article,
            $description,
            $property_no,
            $unit,
            $quantity,
            $amount,
            $remarks
        );
        $stmt->execute();
    }
    $stmt->close();

    /**
     * 3. Insert notification
     */
    $notifSql = "INSERT INTO notifications (type, message, user_id, created_at) 
                 VALUES ('asset_transfer', ?, 0, NOW())";
    $stmt = $conn->prepare($notifSql);
    $message = "Asset transfer PTR No. {$ptr_no} was made (Type: {$type}).";
    $stmt->bind_param("s", $message);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo json_encode(["success" => true, "message" => "Transfer created successfully", "type" => $type]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conn->close();
?>
