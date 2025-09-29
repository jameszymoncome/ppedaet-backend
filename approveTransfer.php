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
$conn = getDatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ptr_no = $_POST['ptr_no'] ?? '';
    $approved_by = $_POST['approved_by'] ?? '';
    $status = $_POST['status'] ?? 'Approved';

    if (!$ptr_no) {
        echo json_encode(["success" => false, "message" => "Missing ptr_no"]);
        exit;
    }

    // ✅ Only update approval info, no file required
    $stmt = $conn->prepare("UPDATE asset_transfer SET status=?, approved_by=? WHERE ptr_no=?");
    $stmt->bind_param("sss", $status, $approved_by, $ptr_no);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Transfer approved"]);
    } else {
        echo json_encode(["success" => false, "message" => "DB update failed"]);
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
}

$conn->close();
