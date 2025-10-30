<?php
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
$database = new Database();
$conn = $database->conn;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

$ptr_no = $_POST['ptr_no'] ?? '';
$status = $_POST['status'] ?? 'Completed';

if (!$ptr_no) {
    echo json_encode(["success" => false, "message" => "Missing ptr_no"]);
    exit;
}

if (!isset($_FILES['signed_doc']) || $_FILES['signed_doc']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "message" => "No file uploaded"]);
    exit;
}

/* save file */
$uploadDir = 'uploads/signed_docs/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$fileName = uniqid() . "_" . basename($_FILES['signed_doc']['name']);
$filePath = $uploadDir . $fileName;

if (!move_uploaded_file($_FILES['signed_doc']['tmp_name'], $filePath)) {
    echo json_encode(["success" => false, "message" => "File upload failed"]);
    exit;
}

/* transaction */
$conn->begin_transaction();

try {
    // ✅ 1. Update asset_transfer status
    $updateTransferSql = "UPDATE asset_transfer SET status = ?, signed_doc = ? WHERE ptr_no = ?";
    $stmt = $conn->prepare($updateTransferSql);
    $stmt->bind_param("sss", $status, $filePath, $ptr_no);
    $stmt->execute();
    $stmt->close();

    // ✅ 2. Get all assets involved in this transfer
    $fetchSql = "
        SELECT 
            ati.propertyNo AS item_no,
            atf.type AS asset_type,
            atf.to_officer AS new_owner,
            atf.transfer_date
        FROM asset_transfer_items ati
        JOIN asset_transfer atf ON ati.transfer_id = atf.id
        WHERE atf.ptr_no = ?
    ";
    $stmt = $conn->prepare($fetchSql);
    $stmt->bind_param("s", $ptr_no);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $item_no = $row['item_no'];
        $asset_type = $row['asset_type'];  // could be PAR, ICS, or PTR
        $new_owner = $row['new_owner'];
        $transfer_date = $row['transfer_date'];

        // ✅ 3. If transferring from a PTR, trace back to its original PAR/ICS
        if ($asset_type === 'PTR') {
            $traceSql = "
                SELECT origin_type, origin_user_id
                FROM assets
                WHERE item_no = ? AND type = 'PTR'
                ORDER BY id ASC LIMIT 1
            ";
            $traceStmt = $conn->prepare($traceSql);
            $traceStmt->bind_param("s", $item_no);
            $traceStmt->execute();
            $traceResult = $traceStmt->get_result();
            $traceData = $traceResult->fetch_assoc();
            $traceStmt->close();

            $origin_type = $traceData['origin_type'] ?? 'PAR';
            $origin_user = $traceData['origin_user_id'] ?? null;
        } else {
            // ✅ 4. If transferring directly from PAR or ICS
            $origin_type = $asset_type;

            $getAssetSql = "SELECT current_user_id FROM assets WHERE item_no = ? AND type = ?";
            $getStmt = $conn->prepare($getAssetSql);
            $getStmt->bind_param("ss", $item_no, $asset_type);
            $getStmt->execute();
            $assetResult = $getStmt->get_result();
            $original = $assetResult->fetch_assoc();
            $getStmt->close();

            $origin_user = $original['current_user_id'] ?? null;
        }

        // ✅ 5. Insert the new PTR record (trace origin correctly)
        $insertSql = "
            INSERT INTO assets (item_no, type, current_user_id, date_acquired, origin_type, origin_user_id)
            VALUES (?, 'PTR', ?, ?, ?, ?)
        ";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("ssssi", $item_no, $new_owner, $transfer_date, $origin_type, $origin_user);
        $insertStmt->execute();
        $insertStmt->close();
    }

    $stmt->close();
    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Transfer completed successfully. Origin traced correctly."
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "success" => false,
        "message" => "Transaction failed: " . $e->getMessage()
    ]);
}
