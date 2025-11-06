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
$database = new Database();
$conn = $database->conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ptr_no = $_POST['ptr_no'] ?? '';

    if (!$ptr_no) {
        echo json_encode(["success" => false, "message" => "PTR number is required"]);
        exit;
    }

    if (!isset($_FILES['signed_doc']) || $_FILES['signed_doc']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(["success" => false, "message" => "No valid file uploaded"]);
        exit;
    }

    $uploadDir = __DIR__ . '/uploads/signed_docs/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Limit file type (e.g., PDF only)
    $allowedExtensions = ['pdf', 'png', 'jpg', 'jpeg'];
    $fileExt = strtolower(pathinfo($_FILES['signed_doc']['name'], PATHINFO_EXTENSION));

    if (!in_array($fileExt, $allowedExtensions)) {
        echo json_encode(["success" => false, "message" => "Invalid file type"]);
        exit;
    }

    // Generate safe file name
    $fileName = uniqid('signed_', true) . '.' . $fileExt;
    $filePath = 'uploads/signed_docs/' . $fileName;
    $fullPath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['signed_doc']['tmp_name'], $fullPath)) {
        // Save relative path to DB (so front-end can access easily)
        $stmt = $conn->prepare("
            UPDATE asset_transfer 
            SET status = 'Accepted - Awaiting for Approval', signed_doc = ? 
            WHERE ptr_no = ?
        ");
        $stmt->bind_param("ss", $filePath, $ptr_no);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "File uploaded and transfer updated successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Database update failed"]);
        }
        $stmt->close();
    } else {
        echo json_encode(["success" => false, "message" => "Failed to move uploaded file"]);
    }
}

$conn->close();
