<?php
// add_account.php

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
    // Get the database connection
    $conn = getDatabaseConnection();

    // Read JSON input
    $data = json_decode(file_get_contents("php://input"), true);

    $lastname = $data['lastname'] ?? '';
    $firstname = $data['firstname'] ?? '';
    $middlename = $data['middlename'] ?? '';
    $suffix = $data['suffix'] ?? '';
    $email = $data['email'] ?? '';
    $contactNumber = $data['contactNumber'] ?? '';
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    $role = $data['role'] ?? '';
    $department = $data['department'] ?? '';

    // Validate required fields
    if (empty($lastname) || empty($firstname) || empty($email) || empty($username) || empty($password) || empty($role)) {
        echo json_encode(["success" => false, "message" => "Please fill out all required fields."]);
        exit();
    }

    // Check if username exists
    $checkStmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Username already exists."]);
        exit();
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insert new user
    $insertSQL = "
        INSERT INTO users (lastname, firstname, middlename, suffix, email, contactNumber, username, password, role, department)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    $stmt = $conn->prepare($insertSQL);
    $stmt->bind_param(
        "ssssssssss",
        $lastname,
        $firstname,
        $middlename,
        $suffix,
        $email,
        $contactNumber,
        $username,
        $hashedPassword,
        $role,
        $department
    );

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Account created successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error saving account"]);
    }

    $conn->close();

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>
