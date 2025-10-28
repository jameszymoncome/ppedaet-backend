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

try {
    $database = new Database();
    $conn = $database->conn;
    
    // Read and decode JSON input
    $input = json_decode(file_get_contents("php://input"), true);

    // Now you can access values from JS
    $docsNo = $input['docsNo'] ?? '';
    $types = $input['types'] ?? '';
    $currentStep = $input['currentStep'] ?? '';
    $selectedItems = $input['selectedItems'] ?? [];

    if ($types === 'PAR') {
        if ($currentStep === 1){
            $stmt = $conn->prepare("UPDATE par SET downloadedForm = 'Upload Scanned Form' WHERE parNo = ? AND type = 'PAR'");
            $stmt->bind_param("s", $docsNo);
            $stmt->execute();
        }
        if ($currentStep === 2){
            $stmt = $conn->prepare("UPDATE par SET downloadedForm = 'Confirmation' WHERE parNo = ? AND type = 'PAR'");
            $stmt->bind_param("s", $docsNo);
            $stmt->execute();
        }
        if ($currentStep === 3){
            $stmt = $conn->prepare("UPDATE par SET status = 'Assigned', downloadedForm = 'Confirmed' WHERE parNo = ? AND type = 'PAR'");
            $stmt->bind_param("s", $docsNo);
            $stmt->execute();

            foreach($selectedItems as $items){
                $stmts = $conn->prepare("INSERT INTO assets(item_no, current_user_id, type, date_acquired) VALUES (?, ?, ?, ?)");
                $stmts->bind_param("siss", $items['itemNo'], $items['userID'], $items['type'], $items['dateAcquired']);
                $stmts->execute();
            }
        }
    } else if ($types === 'ICS') {
        if ($currentStep === 1){
            $stmt = $conn->prepare("UPDATE ics SET downloadedForm = 'Upload Scanned Form' WHERE icsNo = ? AND type = 'ICS'");
            $stmt->bind_param("s", $docsNo);
            $stmt->execute();
        }
        if ($currentStep === 2){
            $stmt = $conn->prepare("UPDATE ics SET downloadedForm = 'Confirmation' WHERE icsNo = ? AND type = 'ICS'");
            $stmt->bind_param("s", $docsNo);
            $stmt->execute();
        }
        if ($currentStep === 3){
            $stmt = $conn->prepare("UPDATE ics SET status = 'Assigned', downloadedForm = 'Confirmed' WHERE icsNo = ? AND type = 'ICS'");
            $stmt->bind_param("s", $docsNo);
            $stmt->execute();

            foreach($selectedItems as $items){
                $stmts = $conn->prepare("INSERT INTO assets(item_no, current_user_id, type, date_acquired) VALUES (?, ?, ?, ?)");
                $stmts->bind_param("siss", $items['itemNo'], $items['userID'], $items['type'], $items['dateAcquired']);
                $stmts->execute();
            }
        }
    }

    echo json_encode([
        "success" => true,
        "message" => "Done Updating"
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}