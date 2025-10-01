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

// --- 1. Insert user ---
$userSql = "INSERT INTO users 
    (lastname, firstname, middlename, suffix, email, contactNumber, username, password, department, position, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$userStmt = $conn->prepare($userSql);

if (!$userStmt) {
    echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
    exit;
}

$userStmt->bind_param(
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

if ($userStmt->execute()) {
    $user_id = $conn->insert_id;
    $userStmt->close();

    // --- 2. Insert notification ---
    $notifSql = "INSERT INTO notifications (type, message, user_id, created_at) 
                 VALUES ('signup', ?, 0, NOW())";
    $notifStmt = $conn->prepare($notifSql);
    $message = "New signup: {$firstname} {$lastname}";
    $notifStmt->bind_param("s", $message);
    $notifStmt->execute();
    $notifStmt->close();

    // --- 3. Optional: Notify WebSocket server ---
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

    echo json_encode(["success" => true, "message" => "Signup successful"]);
} else {
    echo json_encode(["success" => false, "message" => "Execute failed: " . $userStmt->error]);
    $userStmt->close();
}

$conn->close();
?>
