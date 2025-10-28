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
    // Get database connection
    $database = new Database();
    $conn = $database->conn;

    $role = $_GET['role'] ?? '';
    $usersID = $_GET['usersID'] ?? '';
    $departments = $_GET['departments'] ?? '';

    $sql = "SELECT
                assets.id,
                assets.item_no,
                CONCAT(par.description, ' ' ,par.model) AS name,
                par.article AS category,
                users.department,
                CONCAT(users.firstname, ' ', users.middlename, ' ', users.lastname) AS employee,
                assets.inspection_status AS conditions,
                par.unitCost
                FROM assets
                JOIN par ON par.propertyNo = assets.item_no AND par.type = assets.type
                INNER JOIN users ON users.user_id = assets.current_user_id";
    
    if ($role === 'EMPLOYEE') {
        $sql .= " WHERE users.user_id = ?";
    } elseif ($role === 'ADMIN') {
        $sql .= " WHERE users.department = ?";
    }

    $sql .= " UNION ALL

                SELECT
                assets.id,
                assets.item_no,
                CONCAT(ics.description, ' ' ,ics.model) AS name,
                ics.article AS category,
                users.department,
                CONCAT(users.firstname, ' ', users.middlename, ' ', users.lastname) AS employee,
                assets.inspection_status AS conditions,
                ics.unitCost
                FROM assets
                JOIN ics ON ics.inventoryNo = assets.item_no AND ics.type = assets.type
                INNER JOIN users ON users.user_id = assets.current_user_id";
    if ($role === 'EMPLOYEE') {
        $sql .= " WHERE users.user_id = ?";
    } elseif ($role === 'ADMIN') {
        $sql .= " WHERE users.department = ?";
    }

    $stmt = $conn->prepare($sql);

    if ($role === 'EMPLOYEE') {
        $stmt->bind_param("ii", $usersID, $usersID);
    } elseif ($role === 'ADMIN') {
        $stmt->bind_param("ss", $departments, $departments);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode([
        "success" => true,
        "count" => count($data),
        "data" => $data
    ]);


} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>