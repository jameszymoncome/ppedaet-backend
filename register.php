<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connection.php';
$conn = getDatabaseConnection();

// Check connection
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "DB connection failed: " . $conn->connect_error]));
}

// Accept both GET and POST
$device_name = $_POST['device_name'] ?? $_GET['device_name'] ?? '';
$ip          = $_POST['ip'] ?? $_GET['ip'] ?? '';

if (empty($device_name) || empty($ip)) {
    echo json_encode(["status" => "error", "message" => "Missing device_name or ip"]);
    exit();
}

// Insert or update device
$sql = "INSERT INTO devices (device_name, ip, last_seen)
        VALUES ('$device_name', '$ip', NOW())
        ON DUPLICATE KEY UPDATE ip='$ip', last_seen=NOW()";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["status" => "success", "message" => "Device registered successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => $conn->error]);
}

$conn->close();
?>
