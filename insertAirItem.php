<?php
// insertAirItem.php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connection.php';

$data = json_decode(file_get_contents("php://input"), true);

$air_no = $data['air_no'] ?? '';
$air_date = $data['air_date'] ?? '';
$fund = $data['fund'] ?? '';
$items = $data['items'] ?? [];

if (!$air_no || !$air_date || !$fund || empty($items)) {
    echo json_encode(["success" => false, "message" => "Missing required fields or items."]);
    exit();
}

try {
    $database = new Database();
    $conn = $database->conn;

    $sql = "INSERT INTO air_items (air_no, air_date, fund, article, description, unit, total_amount)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Statement preparation failed: " . $conn->error);
    }

    foreach ($items as $item) {
        $article = $item['quantity'] ?? '';
        $description = $item['description'] ?? '';
        $unit = $item['unit'] ?? '';
        $total = $item['amount'] ?? 0;

        $stmt->bind_param("ssssssd", $air_no, $air_date, $fund, $article, $description, $unit, $total);

        if (!$stmt->execute()) {
            throw new Exception("Execution failed: " . $stmt->error);
        }
    }

    echo json_encode(["success" => true, "message" => "Items saved successfully."]);
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
