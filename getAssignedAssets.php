<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\getAssignedAssets.php
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
$conn = getDatabaseConnection();

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
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
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

echo json_encode(["assets" => $assets]);
$conn->close();
?>