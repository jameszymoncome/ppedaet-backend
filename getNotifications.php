<?php
// filepath: c:\xampp\htdocs\ppedaet-backend\getNotifications.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once 'db_connection.php';

$user_id = $_GET['user_id'] ?? 0;
$database = new Database();
$conn = $database->conn;

$sql = "SELECT * FROM notifications WHERE user_id = 0 OR user_id = ? ORDER BY created_at DESC LIMIT 50";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
echo json_encode(["notifications" => $notifications]);
$conn->close();
?>