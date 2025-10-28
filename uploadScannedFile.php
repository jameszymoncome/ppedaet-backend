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
    if (isset($_FILES['file']) && isset($_POST['docNos']) && isset($_POST['docTypes'])) {
        $file = $_FILES['file'];
        $docNos = $_POST['docNos'];
        $docTypes = $_POST['docTypes'];

        $maxSize = 50 * 1024 * 1024; // 50MB limit
        $fileType = mime_content_type($file['tmp_name']);

        if ($file['size'] > $maxSize) {
            http_response_code(400);
            echo json_encode(["error" => "File too large."]);
            exit;
        }

        if (!in_array($fileType, ["application/pdf", "image/jpeg", "image/png"])) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid file type."]);
            exit;
        }

        // Read the actual file content
        $fileData = file_get_contents($file['tmp_name']);
        $fileName = basename($file['name']);

        // Insert or update file in the database
        $stmt = $conn->prepare("INSERT INTO files (airNo, fileName, fileData, fileType, formType) VALUES (?, ?, ?, ?, ?)");
        $null = NULL; // required for sending blob
        $stmt->bind_param("ssbss", $docNos, $fileName, $null, $fileType, $docTypes);
        $stmt->send_long_data(2, $fileData);

        // if ($stmt->execute()) {
        //     // Begin transaction for status updates
        //     $conn->begin_transaction();

        //     $stmt1 = $conn->prepare("UPDATE ics SET status = 'Assigned' WHERE airNo = ? AND type = ?");
        //     $stmt1->bind_param("ss", $docNos, $docTypes);
        //     $stmt1->execute();
        //     $affected1 = $stmt1->affected_rows;
        //     $stmt1->close();

        //     $stmt2 = $conn->prepare("UPDATE par SET status = 'Assigned' WHERE airNo = ? AND type = ?");
        //     $stmt2->bind_param("ss", $docNos, $docTypes);
        //     $stmt2->execute();
        //     $affected2 = $stmt2->affected_rows;
        //     $stmt2->close();

        //     if ($affected1 > 0 || $affected2 > 0) {
        //         $conn->commit();
        //         echo json_encode([
        //             "success" => true,
        //             "message" => "File uploaded and status updated successfully",
        //             "fileName" => $fileName,
        //             "fileType" => $fileType
        //         ]);
        //     } else {
        //         $conn->rollback();
        //         echo json_encode(["warning" => "File saved but no matching record found for status update"]);
        //     }
        // } else {
        //     echo json_encode(["error" => "Database error: " . $stmt->error]);
        // }

        if ($stmt->execute()) {
            echo json_encode([
                "success" => true,
                "message" => "File uploaded successfully",
                "fileName" => $fileName,
                "fileType" => $fileType
            ]);
        } else {
            echo json_encode([
                "error" => "Database error: " . $stmt->error
            ]);
        }

        $stmt->close();
        $conn->close();
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Missing file or parameters"]);
    }
}
?>
