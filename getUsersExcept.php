<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\getUsersExcept.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connection.php';

$exclude_id = isset($_GET['exclude_id']) ? intval($_GET['exclude_id']) : 0;

$conn = getDatabaseConnection();

$sql = "SELECT user_id, firstname, lastname, department FROM users WHERE user_id != ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $exclude_id);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode(["users" => $users]);
$conn->close();
?>