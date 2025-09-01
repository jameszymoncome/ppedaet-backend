<?php
// filepath: c:\xampp\htdocs\backend\signup.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
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

$lastname      = $data['lastname'] ?? '';
$firstname     = $data['firstname'] ?? '';
$middlename    = $data['middlename'] ?? '';
$suffix        = $data['suffix'] ?? '';
$email         = $data['email'] ?? '';
$contactNumber = $data['contactNumber'] ?? '';
$username      = $data['username'] ?? '';
$password      = password_hash($data['password'] ?? '', PASSWORD_DEFAULT);
$department    = $data['department'] ?? '';
$position      = $data['position'] ?? '';
$created_at    = date('Y-m-d H:i:s');

// Insert user into database
$sql = "INSERT INTO users 
    (lastname, firstname, middlename, suffix, email, contactNumber, username, password, department, position, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param(
    "sssssssssss",
    $lastname,
    $firstname,
    $middlename,
    $suffix,
    $email,
    $contactNumber,
    $username,
    $password,
    $department,
    $position,
    $created_at
);

    // $notif_sql = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)";
    // $notif_stmt = $conn->prepare($notif_sql);
    // $message = $firstname . " " . $lastname . " has signed up.";
    // $type = "signup";
    // $notif_stmt->bind_param("iss", $user_id, $message, $type);
    // $notif_stmt->execute();
    // $notif_stmt->close();

if ($stmt->execute()) {
    // Optional: Notify WebSocket server on successful signup
    $fullName = $firstname . ' ' . $lastname;
    $notifyData = [
        'fullName'   => $fullName,
        'department' => $department
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($notifyData),
            'timeout' => 2
        ]
    ];
    $context  = stream_context_create($options);
    @file_get_contents('http://localhost:8081/notify-signup', false, $context);

    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Execute failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
