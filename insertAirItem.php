<?php
require_once 'db_connection.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$air_no = $data['air_no'] ?? '';
$air_date = $data['air_date'] ?? '';
$fund = $data['fund'] ?? '';
$items = $data['items'] ?? [];

$conn = getDatabaseConnection();

$sql = "INSERT INTO air_items (air_no, air_date, fund, article, description, model, serial_no, unit, unit_cost, total_amount)
        VALUES (?, ?, ?, ?, ?, '', '', ?, 0, ?)";

$stmt = $conn->prepare($sql);

foreach ($items as $item) {
    $article = $item['quantity'] ?? '';
    $description = $item['description'] ?? '';
    $unit = $item['unit'] ?? '';
    $total = $item['amount'] ?? 0;

    $stmt->bind_param("sssssds", $air_no, $air_date, $fund, $article, $description, $unit, $total);
    $stmt->execute();
}

echo json_encode(["success" => true, "message" => "Items saved successfully."]);
