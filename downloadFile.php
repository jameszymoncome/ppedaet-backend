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

$database = new Database();
$conn = $database->conn;

$airNo = $_GET['airNo'] ?? '';
$formType = $_GET['formType'] ?? '';

if (empty($airNo) || empty($formType)) {
    echo json_encode(["success" => false, "message" => "Missing airNo or formType"]);
    exit();
}

$stmt = $conn->prepare("SELECT fileName, fileData, fileType FROM files WHERE airNo = ? AND formType = ?");
$stmt->bind_param("ss", $airNo, $formType);
$stmt->execute();
$result = $stmt->get_result();

$files = [];
while ($row = $result->fetch_assoc()) {
    $files[] = [
        "fileName" => $row['fileName'],
        "fileType" => $row['fileType'],
        "fileData" => base64_encode($row['fileData']) // Encode binary data
    ];
}

$stmt->close();
$conn->close();

echo json_encode([
    "success" => true,
    "files" => $files
]);
?>
