<?php
// ad_retrieve_users.php

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
    // Get DB connection
    $database = new Database();
    $conn = $database->conn;

    // Fetch department from GET parameter
$department = isset($_GET['department']) ? trim($_GET['department']) : '';

if (empty($department)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid department.'
    ]);
    exit();
}

// Query users by department
$sql = "
    SELECT user_id, 
           CONCAT(lastname, ', ', firstname, ' ', middlename) AS full_name,
           department, role, email, contactNumber, position, acc_status
    FROM users
    WHERE department = ?
    ORDER BY lastname ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $department);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    throw new Exception("Query failed: " . $conn->error);
}

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode(["success" => true, "data" => $users]);

$stmt->close();
$conn->close();

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error retrieving users: " . $e->getMessage()
    ]);
}
