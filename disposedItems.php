<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\disposedItems.php

error_reporting(E_ALL);
ini_set('display_errors', 1);
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
    $conn = getDatabaseConnection();

    // Read JSON body from React request
    $input = json_decode(file_get_contents("php://input"), true);

    if (isset($input['itemNos']) && is_array($input['itemNos'])) {
        $itemNos = $input['itemNos']; 

        // Prepare placeholders (?, ?, ?)
        $placeholders = implode(',', array_fill(0, count($itemNos), '?'));

        // 🔹 Update PAR table
        $sqlPar = "UPDATE par 
                   SET status = 'Disposed' 
                   WHERE tagID IN ($placeholders)";
        $stmtPar = $conn->prepare($sqlPar);
        $stmtPar->bind_param(str_repeat('s', count($itemNos)), ...$itemNos);
        $stmtPar->execute();
        $parUpdated = $stmtPar->affected_rows;

        // 🔹 Update ICS table
        $sqlIcs = "UPDATE ics 
                   SET status = 'Disposed' 
                   WHERE tagID IN ($placeholders)";
        $stmtIcs = $conn->prepare($sqlIcs);
        $stmtIcs->bind_param(str_repeat('s', count($itemNos)), ...$itemNos);
        $stmtIcs->execute();
        $icsUpdated = $stmtIcs->affected_rows;

        echo json_encode([
            "success" => true,
            "message" => "Updated successfully",
            "parUpdated" => $parUpdated,
            "icsUpdated" => $icsUpdated,
            "updatedItems" => $itemNos
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "No itemNos provided"
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
