<?php
// ...existing code...
// Disable PHP notices/warnings in production JSON endpoints
error_reporting(0);
ini_set('display_errors', 0);

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connection.php';
$database = new Database();
$conn = $database->conn;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid request method"]);
        exit;
    }

    $ptr_no = $_POST['ptr_no'] ?? '';
    $approved_by = $_POST['approved_by'] ?? 'Admin';
    $status = $_POST['status'] ?? 'Approved';

    if (!$ptr_no) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Missing ptr_no"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE asset_transfer SET status = ?, approved_by = ? WHERE ptr_no = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sss", $status, $approved_by, $ptr_no);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Transfer approved"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database update failed", "error" => $stmt->error]);
    }

    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error", "error" => $e->getMessage()]);
}

$conn->close();
?>