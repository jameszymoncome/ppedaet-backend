<?php
// updateProfile.php

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

try {
    $database = new Database();
    $conn = $database->conn;

    // Read JSON input
    $data = json_decode(file_get_contents("php://input"), true);

    $user_id       = $data['user_id'] ?? null;
    $lastname      = $data['lastname'] ?? '';
    $firstname     = $data['firstname'] ?? '';
    $middlename    = $data['middlename'] ?? '';
    $suffix        = $data['suffix'] ?? '';
    $email         = $data['email'] ?? '';
    $contactNumber = $data['contactNumber'] ?? '';
    $username      = $data['username'] ?? '';
    $password      = $data['password'] ?? '';
    $role          = $data['role'] ?? '';
    $department    = $data['department'] ?? '';
    $position      = $data['position'] ?? '';

    // Validate required fields
    if (empty($user_id) || empty($lastname) || empty($firstname) || empty($email) || empty($username) || empty($role) || empty($position)) {
        echo json_encode(["success" => false, "message" => "Please fill out all required fields."]);
        exit();
    }

    // Optional: hash the password if it's changed (you may adjust this logic if needed)
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Update the user
    $updateSQL = "
        UPDATE users SET
            lastname = ?,
            firstname = ?,
            middlename = ?,
            suffix = ?,
            email = ?,
            contactNumber = ?,
            username = ?,
            password = ?,
            role = ?,
            department = ?,
            position = ?
        WHERE user_id = ?
    ";
    $stmt = $conn->prepare($updateSQL);
    $stmt->bind_param(
        "sssssssssssi",
        $lastname,
        $firstname,
        $middlename,
        $suffix,
        $email,
        $contactNumber,
        $username,
        $hashedPassword,
        $role,
        $department,
        $position,
        $user_id
    );

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Profile updated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update profile"]);
    }

    $conn->close();

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
