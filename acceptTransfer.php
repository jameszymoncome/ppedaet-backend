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

    if (isset($_FILES['signed_doc']) && $_FILES['signed_doc']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/signed_docs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . "_" . basename($_FILES['signed_doc']['name']);
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['signed_doc']['tmp_name'], $filePath)) {
            // Update DB: mark accepted + store file path
            $stmt = $conn->prepare("UPDATE asset_transfer SET status='Accepted - Awaiting for Approval', signed_doc=? WHERE ptr_no=?");
            $stmt->bind_param("ss", $filePath, $ptr_no);

            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "File uploaded and transfer accepted"]);
            } else {
                echo json_encode(["success" => false, "message" => "DB update failed"]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "File upload failed"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "No file uploaded"]);
    }
}
$conn->close();
