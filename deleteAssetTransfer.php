<?php
// filepath: c:\xampp\htdocs\ppedaet-backend\deleteAssetTransfer.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connection.php';

$ptr_no = $_GET['ptr_no'] ?? '';

if (!$ptr_no) {
    echo json_encode(["success" => false, "message" => "Missing PTR No."]);
    exit;
}

$conn = getDatabaseConnection();

// Delete related items first (if you have a linking table)
$conn->query("DELETE FROM asset_transfer_items WHERE transfer_id IN (SELECT id FROM asset_transfer WHERE ptr_no = '$ptr_no')");

// Delete the transfer record
$sql = "DELETE FROM asset_transfer WHERE ptr_no = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $ptr_no);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>