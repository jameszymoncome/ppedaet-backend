<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\disposeItems.php

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
    $database = new Database();
    $conn = $database->conn;

    // Read JSON body from React request
    $input = json_decode(file_get_contents("php://input"), true);
    $allItems = $input['allItems'] ?? [];
    
    foreach ($allItems as $item) {
        $tagID = $item['tagID'];

        // Update PAR table
        $sqlPar = "UPDATE par 
                   SET status = 'Disposed' 
                   WHERE tagID = ? AND type = ?";
        $stmtPar = $conn->prepare($sqlPar);
        $stmtPar->bind_param('ss', $tagID, $item['type']);
        $stmtPar->execute();

        // Update ICS table
        $sqlIcs = "UPDATE ics 
                   SET status = 'Disposed' 
                   WHERE tagID = ? AND type = ?";
        $stmtIcs = $conn->prepare($sqlIcs);
        $stmtIcs->bind_param('ss', $tagID, $item['type']);
        $stmtIcs->execute();
    }

    echo json_encode([
        "success" => true,
        "message" => "Items disposed successfully",
        "disposedItems" => $allItems
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
