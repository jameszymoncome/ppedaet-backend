<?php
// filepath: c:\xampp\htdocs\ppedaet-backend\getAssets.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once 'db_connection.php';

$user_id = $_GET['user_id'] ?? 0;
$conn = getDatabaseConnection();

$sql = "SELECT `item_no`, `article`, `description`, `model`, `serial_no`, `document_type`, `document_no`, `inspection_status`, `status`, `date_acquired` FROM `assets` WHERE `status` = 'Assigned' AND `current_user_id` = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$assets = [];
while ($row = $result->fetch_assoc()) {
    $assets[] = $row;
}
echo json_encode(["assets" => $assets]);
$conn->close();
?>