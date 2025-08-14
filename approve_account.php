<?php
require_once 'db_connection.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$conn = getDatabaseConnection(); // Get connection using your db_connection.php class

$data = json_decode(file_get_contents("php://input"), true);
$userId = $data['user_id'] ?? null;
$role = $data['role'] ?? null;

if ($userId && $role) {
  // Update the account's status and set the role
  $stmt = $conn->prepare("UPDATE users SET acc_status = 'Active', role = ? WHERE user_id = ?");
  $stmt->bind_param("si", $role, $userId);
  if ($stmt->execute()) {
    echo json_encode(['success' => true]);
  } else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
  }
}

$stmt->close();
$conn->close();
?>
