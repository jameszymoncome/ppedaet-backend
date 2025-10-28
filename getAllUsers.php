<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connection.php';

try {
    $database = new Database();
    $conn = $database->conn;

    $department = $_GET['department'] ?? '';

    $sql = "SELECT
                CONCAT(u.firstname, ' ', u.middlename, ' ', u.lastname) AS name,
                u.position,
                COUNT(a.id) AS assetCount
            FROM users u
            LEFT JOIN assets a ON a.current_user_id = u.user_id
            WHERE u.department = ?
            GROUP BY u.user_id, u.firstname, u.middlename, u.lastname, u.position
            ORDER BY assetCount DESC;

    ";

    // Prepare and execute the query
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $department);
    $stmt->execute();
    $result = $stmt->get_result();

    $usersList = [];
    while ($row = $result->fetch_assoc()) {
        $usersList[] = $row;
    }

    echo json_encode([
        "success" => true,
        "data" => $usersList
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
