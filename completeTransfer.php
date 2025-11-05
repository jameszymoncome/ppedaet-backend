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

/* Save file */
$uploadDir = 'uploads/signed_docs/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$fileName = uniqid() . "_" . basename($_FILES['signed_doc']['name']);
$filePath = $uploadDir . $fileName;

if (!move_uploaded_file($_FILES['signed_doc']['tmp_name'], $filePath)) {
    echo json_encode(["success" => false, "message" => "File upload failed"]);
    exit;
}

/* Start transaction */
$conn->begin_transaction();

try {
    // ✅ 1. Update asset_transfer status
    $updateTransferSql = "UPDATE asset_transfer SET status = ?, signed_doc = ? WHERE ptr_no = ?";
    $stmt = $conn->prepare($updateTransferSql);
    $stmt->bind_param("sss", $status, $filePath, $ptr_no);
    $stmt->execute();
    $stmt->close();

    // ✅ 2. Fetch all items in this PTR transfer
    $fetchSql = "
        SELECT 
            ati.propertyNo AS item_no,
            atf.type AS asset_type,
            atf.to_officer AS new_owner,
            atf.from_officer AS prev_owner,
            atf.transfer_date
        FROM asset_transfer_items ati
        JOIN asset_transfer atf ON ati.transfer_id = atf.id
        WHERE atf.ptr_no = ?
    ";
    $stmt = $conn->prepare($fetchSql);
    $stmt->bind_param("s", $ptr_no);
    $stmt->execute();
    $result = $stmt->get_result();

    // ✅ 3. Update each asset instead of inserting new ones
    while ($row = $result->fetch_assoc()) {
        $item_no = $row['item_no'];
        $asset_type = $row['asset_type']; // PAR / ICS / PTR
        $new_owner = $row['new_owner'];
        $prev_owner = $row['prev_owner'];
        $transfer_date = $row['transfer_date'];

        // ✅ 4. Update the existing asset record
        $updateAssetSql = "
            UPDATE assets
            SET 
                current_user_id = ?,     -- new owner
                type = 'PTR',            -- now marked as transferred
                date_acquired = ?,       -- transfer date
                origin_type = ?,         -- original document type (PAR or ICS)
                origin_user_id = ?       -- previous owner
            WHERE item_no = ?
        ";
        $updateStmt = $conn->prepare($updateAssetSql);
        $updateStmt->bind_param("sssis", $new_owner, $transfer_date, $asset_type, $prev_owner, $item_no);
        $updateStmt->execute();
        $updateStmt->close();
    }

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "PTR transfer completed successfully — existing assets updated without duplication."
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "success" => false,
        "message" => "Transaction failed: " . $e->getMessage()
    ]);
}
?>
