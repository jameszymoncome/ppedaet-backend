<?php
// filepath: c:\xampp\htdocs\ppedaet-backend\getAssetTransferItems.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once 'db_connection.php';

$database = new Database();
$conn = $database->conn;

$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["error" => "Missing user_id"]);
    exit;
}

$sql = "
SELECT 
  at.quantity,
  at.unit,
  a.from_officer AS from_officer,
  CONCAT(u_to.firstname, ' ', u_to.lastname) AS to_officer_name,
  CONCAT(u_from.firstname, ' ', u_from.lastname) AS from_officer_name,
  at.description,
  at.propertyNo,
  at.amount,
  at.remarks
FROM asset_transfer_items AS at
LEFT JOIN asset_transfer AS a ON a.id = at.transfer_id
LEFT JOIN par AS p ON p.propertyNo = at.propertyNo
LEFT JOIN ics AS i ON i.inventoryNo = at.propertyNo
LEFT JOIN air_items AS ai ON ai.air_no = p.airNo OR ai.air_no = i.airNo
LEFT JOIN users AS u_from ON a.from_officer = u_from.user_id
LEFT JOIN users AS u_to ON a.to_officer = u_to.user_id
WHERE a.from_officer = ?;
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode(["items" => $items]);

$conn->close();
?>