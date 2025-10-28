<?php
// updateTagID.php

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
    $database = new Database();
    $conn = $database->conn;

    $data = json_decode(file_get_contents("php://input"), true);

    $itemNo = $data['itemNo'] ?? '';
    $tableType = $data['tableType'] ?? '';

    if ($tableType === "ICS") {
        $stmt = $conn->prepare("UPDATE ics SET tagID = '', status = 'For Tagging' WHERE inventoryNo = ?");
    } else if ($tableType === "PAR") {
        $stmt = $conn->prepare("UPDATE par SET tagID = '', status = 'For Tagging' WHERE propertyNo = ?");
    }

    $stmt->bind_param("s", $itemNo);
    $stmt->execute();

    echo json_encode(["success" => true, "message" => "Tag ID cleared."]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
