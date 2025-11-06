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
$database = new Database();
$conn = $database->conn;

$item_no = $_GET['item_no'] ?? null;
$user_id = $_GET['user_id'] ?? null;

$sql = "
    SELECT 
        ath.*,
        u1.firstname AS from_user_firstname,
        u1.lastname AS from_user_lastname,
        u2.firstname AS to_user_firstname,
        u2.lastname AS to_user_lastname
    FROM asset_transfer_history ath
    LEFT JOIN users u1 ON ath.from_user_id = u1.user_id
    LEFT JOIN users u2 ON ath.to_user_id = u2.user_id
    WHERE 1=1
";

$params = [];
$types = "";

if ($item_no) {
    $sql .= " AND ath.item_no = ?";
    $params[] = $item_no;
    $types .= "s";
}
if ($user_id) {
    $sql .= " AND (ath.from_user_id = ? OR ath.to_user_id = ?)";
    $params[] = $user_id;
    $params[] = $user_id;
    $types .= "ii";
}

$sql .= " ORDER BY ath.transfer_date DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$history = [];

while ($row = $result->fetch_assoc()) {
    $history[] = [
        'item_no' => $row['item_no'],
        'from_user' => trim(($row['from_user_firstname'] ?? '') . ' ' . ($row['from_user_lastname'] ?? '')),
        'to_user' => trim(($row['to_user_firstname'] ?? '') . ' ' . ($row['to_user_lastname'] ?? '')),
        'from_type' => $row['from_type'],
        'to_type' => $row['to_type'],
        'transfer_date' => $row['transfer_date'],
        'document_no' => $row['document_no'],
        'remarks' => $row['remarks']
    ];
}

echo json_encode([
    "success" => true,
    "history" => $history
]);
$conn->close();
?>
