<?php
// delete_account.php

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Respond to preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connection.php';

try {
    $conn = getDatabaseConnection();
    $data = json_decode(file_get_contents("php://input"), true);

    $userId = $data['user_id'] ?? null;
    $requesterRole = $data['requester_role'] ?? null;

    if (!$userId || !$requesterRole) {
        echo json_encode(["success" => false, "message" => "Missing data."]);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "User deleted successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to delete user."]);
    }

    $conn->close();

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
