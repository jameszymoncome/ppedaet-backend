<?php
// signup.php

// Allow from any origin
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connection.php';

$conn = getDatabaseConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

$lastname = $data['lastname'] ?? '';
$firstname = $data['firstname'] ?? '';
$middlename = $data['middlename'] ?? '';
$suffix = $data['suffix'] ?? '';
$email = $data['email'] ?? '';
$contactNumber = $data['contactNumber'] ?? '';
$username = $data['username'] ?? '';
$password = password_hash($data['password'] ?? '', PASSWORD_DEFAULT);
$department = $data['department'] ?? '';
$position = $data['position'] ?? '';
$created_at = date('Y-m-d H:i:s');

$sql = "INSERT INTO users (lastname, firstname, middlename, suffix, email, contactNumber, username, password, department, position, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param("sssssssssss", $lastname, $firstname, $middlename, $suffix, $email, $contactNumber, $username, $password, $department, $position, $created_at);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Execute failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
