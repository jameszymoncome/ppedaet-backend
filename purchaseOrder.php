<?php
// purchaseorder.php

error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
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
    $lguBranch = $conn->real_escape_string($data['lguBranch']);
    $mode = $conn->real_escape_string($data['selectedModeProcurement']);
    $prNo = $conn->real_escape_string($data['purchaseRequestNo']);
    $supplierID = $conn->real_escape_string($data['selectedSupplierID']);
    $place = $conn->real_escape_string($data['placeOfDelivery']);
    $dateDelivery = $conn->real_escape_string($data['dateOfDelivery']);
    $term = $conn->real_escape_string($data['selectedDeliveryTerm']);
    $payment = $conn->real_escape_string($data['paymentTerms']);
    $items = $data['items'];

    $countQuery = "SELECT COUNT(*) AS total FROM purchaseorder";
    $countResult = $conn->query($countQuery);
    $countRow = $countResult->fetch_assoc();

    $purchaseOrderID = str_pad($countRow['total'] + 1, 5, '0', STR_PAD_LEFT);

    $stmt = $conn->prepare("INSERT INTO purchaseorder (purchaseorderID, localGovUnit, modeofProcurement, supplierID, placeOfDelivery, dateOfDelivery, deliveryTerm, paymentTerm) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssissss", $purchaseOrderID, $lguBranch, $mode, $supplierID, $place, $dateDelivery, $term, $payment);
    $stmt->execute();

    echo json_encode(['success' => true, 'data' => $lguBranch]);

    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
