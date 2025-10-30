<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\updateTransferStatus.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once 'db_connection.php';

$database = new Database();
$conn = $database->conn;

// ✅ Get POST data
$ptr_no = $_POST['ptr_no'] ?? null;
$status = $_POST['status'] ?? null;

if (!$ptr_no || !$status) {
    echo json_encode(["success" => false, "message" => "Missing ptr_no or status"]);
    exit;
}

// ✅ Update the status in `asset_transfer` table
$query = "UPDATE asset_transfer SET status = ? WHERE ptr_no = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $status, $ptr_no);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Status updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to update status"]);
}

$stmt->close();
$conn->close();
?>
