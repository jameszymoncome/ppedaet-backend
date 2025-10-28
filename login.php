<?php
//login.php

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
    // Get database connection
    $database = new Database();
    $conn = $database->conn;

    // Get the POST data
    $data = json_decode(file_get_contents("php://input"), true);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    // Validate input
    if (empty($username) || empty($password)) {
        echo json_encode(["success" => false, "message" => "Username and password are required"]);
        exit();
    }

    // Query the database for the user
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Invalid username or password"]);
        exit();
    }

    $user = $result->fetch_assoc();

    // Verify the password
    if (!password_verify($password, $user['password'])) {
        echo json_encode(["success" => false, "message" => "Invalid username or password"]);
        exit();
    }

    // Generate a JWT token (you can use a library like Firebase JWT for PHP)
    $token = base64_encode(json_encode(["id" => $user['user_id'], "role" => $user['role'], "exp" => time() + 3600]));

    // Respond with the token and user details
    echo json_encode([
    "success" => true,
    "token" => $token,
    "message" => 'Log in successful!',
    "firstName" => $user['firstname'],
    "lastname" => $user['lastname'],
    "accessLevel" => $user['role'], // return exact role
    "userId" => $user['user_id'],
    "department" => $user['department'],
    "position" => $user['position'],
    "acc_status" => $user['acc_status'],
]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}

?>