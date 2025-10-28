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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ptr_no = $_POST['ptr_no'] ?? '';
    $status = $_POST['status'] ?? 'Completed';

    if (!$ptr_no) {
        echo json_encode(["success" => false, "message" => "Missing ptr_no"]);
        exit;
    }

    // ✅ File upload handling
    if (isset($_FILES['signed_doc']) && $_FILES['signed_doc']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/signed_docs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . "_" . basename($_FILES['signed_doc']['name']);
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['signed_doc']['tmp_name'], $filePath)) {
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
                        atf.type AS asset_type,       -- original type: PAR or ICS
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

                // ✅ 3. For each transferred asset
                while ($row = $result->fetch_assoc()) {
                    $item_no = $row['item_no'];
                    $asset_type = $row['asset_type'];
                    $new_owner = $row['new_owner'];
                    $transfer_date = $row['transfer_date'];

                    // ✅ 4. Get the original asset info
                    $getAssetSql = "SELECT current_user_id FROM assets WHERE item_no = ? AND type = ?";
                    $getStmt = $conn->prepare($getAssetSql);
                    $getStmt->bind_param("ss", $item_no, $asset_type);
                    $getStmt->execute();
                    $assetResult = $getStmt->get_result();
                    $original = $assetResult->fetch_assoc();
                    $getStmt->close();

                    $origin_user = $original['current_user_id'] ?? null;

                    // ✅ 5. Insert new PTR record (keep original PAR/ICS)
                    $insertSql = "
                        INSERT INTO assets (item_no, type, current_user_id, date_acquired, origin_type, origin_user_id)
                        VALUES (?, 'PTR', ?, ?, ?, ?)
                    ";
                    $insertStmt = $conn->prepare($insertSql);
                    // Correct type string: s = string, i = integer
                    $insertStmt->bind_param("ssssi", $item_no, $new_owner, $transfer_date, $asset_type, $origin_user);
                    $insertStmt->execute();
                    $insertStmt->close();
                }

                $stmt->close();
                $conn->commit();

                echo json_encode([
                    "success" => true,
                    "message" => "Transfer completed successfully and PTR records created."
                ]);

            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode([
                    "success" => false,
                    "message" => "Transaction failed: " . $e->getMessage()
                ]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "File upload failed"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "No file uploaded"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
}
?>
