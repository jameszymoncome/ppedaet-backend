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
$conn = getDatabaseConnection();

$targetDir = "uploads/";

if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file']) && isset($_POST['docNos'])) {
        $file = $_FILES['file'];
        $docNos = $_POST['docNos'];
        $fileName = uniqid() . "_" . basename($file['name']);
        $targetFile = $targetDir . $fileName;

        $maxSize = 50 * 1024 * 1024; // 50MB
        $fileType = mime_content_type($file['tmp_name']);

        if ($file['size'] > $maxSize) {
            http_response_code(400);
            echo json_encode(["error" => "File too large."]);
            exit;
        }

        if ($fileType !== "application/pdf" && $fileType !== "image/jpeg" && $fileType !== "image/png" && $fileType !== "image/png") {
            http_response_code(400);
            echo json_encode(["error" => "Invalid file type."]);
            exit;
        }

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            // Update scanned_files table
            $stmt = $conn->prepare("INSERT INTO files(airNo, filePath) VALUES (?, ?)");
            $stmt->bind_param("ss", $docNos, $targetFile);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    // Begin transaction for status updates
                    $conn->begin_transaction();

                    // Update ICS table
                    $stmt1 = $conn->prepare("UPDATE ics SET status = 'Assigned' WHERE airNo = ?");
                    $stmt1->bind_param("s", $docNos);
                    $stmt1->execute();
                    $affected1 = $stmt1->affected_rows;
                    $error1 = $stmt1->error;
                    $stmt1->close();

                    // Update PAR table
                    $stmt2 = $conn->prepare("UPDATE par SET status = 'Assigned' WHERE airNo = ?");
                    $stmt2->bind_param("s", $docNos);
                    $stmt2->execute();
                    $affected2 = $stmt2->affected_rows;
                    $error2 = $stmt2->error;
                    $stmt2->close();

                    // Commit if at least one update succeeded
                    if ($affected1 > 0 || $affected2 > 0) {
                        $conn->commit();
                        echo json_encode([
                            "success" => true,
                            "filePath" => $targetFile,
                            "ics_updated" => $affected1,
                            "par_updated" => $affected2
                        ]);
                    } else {
                        $conn->rollback();
                        echo json_encode([
                            "warning" => "No status updated — airNo may not exist in either table.",
                            "ics_updated" => $affected1,
                            "par_updated" => $affected2,
                            "ics_error" => $error1,
                            "par_error" => $error2
                        ]);
                    }
                } else {
                    echo json_encode([
                        "warning" => "No rows updated in air_items — air_no may not match.",
                        "docNos" => $docNos,
                        "filePath" => $targetFile
                    ]);
                }
            } else {
                echo json_encode([
                    "error" => "DB error on air_items update",
                    "sqlError" => $stmt->error,
                    "docNos" => $docNos
                ]);
            }

            $stmt->close();
            $conn->close();
        } else {
            http_response_code(500);
            echo json_encode(["error" => "File upload failed"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["error" => "No file or docNos provided"]);
    }
}
?>