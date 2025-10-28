<?php
// filepath: c:\xampp\htdocs\ppedaet-backend\IC_getAssets.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once 'db_connection.php';

$database = new Database();
$conn = $database->conn;

// Get userId from query params
$userId = isset($_GET['userId']) ? trim($_GET['userId']) : '';

if (empty($userId)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid userId.'
    ]);
    exit();
}

$sql = "
SELECT DISTINCT
    p.propertyNo AS itemNo,
    CONCAT(u.firstname, ' ', u.lastname) AS assigned_to,
    u.department AS department,
    p.article,
    p.description,
    p.model,
    p.serialNo AS serial_no,
    p.type AS document_type,
    p.parNo AS document_no,
    p.status,
    a.date_acquired
FROM par AS p
LEFT JOIN assets AS a
    ON a.item_no = p.propertyNo
LEFT JOIN users AS u
    ON u.user_id = a.current_user_id
WHERE p.status = 'Assigned' AND u.user_id = ?

UNION ALL

SELECT DISTINCT
    i.inventoryNo AS itemNo,
    CONCAT(u.firstname, ' ', u.lastname) AS assigned_to,
    u.department AS department,
    i.article,
    i.description,
    i.model,
    i.serialNo AS serial_no,
    i.type AS document_type,
    i.icsNo AS document_no,
    i.status,
    a.date_acquired
FROM ics AS i
LEFT JOIN assets AS a
    ON a.item_no = i.inventoryNo
LEFT JOIN users AS u
    ON u.user_id = a.current_user_id
WHERE i.status = 'Assigned' AND u.user_id = ?
";

$stmt = $conn->prepare($sql);

// ✅ Bind both parameters in one array
$stmt->bind_param("ss", $userId, $userId);

$stmt->execute();
$result = $stmt->get_result();

$assets = [];
while ($row = $result->fetch_assoc()) {
    $assets[] = $row;
}

echo json_encode(["assets" => $assets]);

$stmt->close();
$conn->close();
?>
