<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
require_once 'db_connection.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$database = new Database();
$conn = $database->conn;

$sql = "
SELECT 
    p.unit,
    p.description,
    p.propertyNo,
    p.unitCost,
    a.enduser_id,
    'PAR' AS type
FROM par AS p
LEFT JOIN air_items AS a ON a.air_no = p.airNo
LEFT JOIN users AS u ON a.enduser_id = u.user_id
WHERE p.status = 'Assigned' AND u.user_id = ?

UNION ALL

SELECT 
    i.unit,
    i.description,
    i.inventoryNo AS propertyNo,
    i.unitCost,
    a.enduser_id,
    'ICS' AS type
FROM ics AS i
LEFT JOIN air_items AS a ON a.air_no = i.airNo
LEFT JOIN users AS u ON a.enduser_id = u.user_id
WHERE i.status = 'Assigned' AND u.user_id = ?

UNION ALL

SELECT 
    COALESCE(p.unit, i.unit) as unit,
    COALESCE(p.description, i.description) as description,
    a.item_no as propertyNo,
    COALESCE(p.unitCost, i.unitCost) as unitCost,
    a.current_user_id as enduser_id,
    'PTR' AS type
FROM assets a
LEFT JOIN par p ON a.origin_type = 'PAR' AND a.item_no = p.propertyNo
LEFT JOIN ics i ON a.origin_type = 'ICS' AND a.item_no = i.inventoryNo
WHERE a.type = 'PTR' AND a.current_user_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$assets = [];
while ($row = $result->fetch_assoc()) {
    $assets[] = [
        "unit" => $row['unit'],
        "description" => $row['description'],
        "propertyNo" => $row['propertyNo'],
        "unitCost" => $row['unitCost'],
        "type" => $row['type']
    ];
}

echo json_encode([
    "success" => true,
    "assets" => $assets
]);
$conn->close();
?>