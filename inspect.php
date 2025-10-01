<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\inspect.php
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

try {
    $conn = getDatabaseConnection();
    
    // Read and decode JSON input
    $input = json_decode(file_get_contents("php://input"), true);

    // Now you can access values from JS
    $nfcTagID = $input['nfcTagID'] ?? '';
    $selectedCondition = $input['selectedCondition'] ?? '';
    $remarks = $input['remarks'] ?? '';
    $mode = $input['mode'] ?? '';

    if ($mode === 'scan') {
        $stmt = $conn->prepare("INSERT INTO inspectionhistory(tagID, conditions, remarks) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nfcTagID, $selectedCondition, $remarks);
        $stmt->execute();
    } else if ($mode === 'view') {
        $stmt = $conn->prepare("UPDATE inspectionhistory SET conditions = ?, remarks = ? WHERE tagID = ?");
        $stmt->bind_param("sss", $selectedCondition, $remarks, $nfcTagID);
        $stmt->execute();
    } else if ($mode === 'report') {
        $stmt = $conn->prepare("INSERT INTO inspectionhistory (tagID, updates) VALUES (?, ?)");
        $stmt->bind_param("ss", $nfcTagID, $selectedCondition);
        $stmt->execute();
    }

    echo json_encode([
        "success" => true,
        "message" => "Done Inspecting"
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}