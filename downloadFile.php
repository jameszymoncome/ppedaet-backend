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
require_once 'fpdf.php';

$conn = getDatabaseConnection();


if (isset($_GET['air_no'])) {
    $airNo = $_GET['air_no'];

    // Get all file paths with this airNo
    $stmt = $conn->prepare("SELECT filePath FROM files WHERE airNo = ?");
    $stmt->bind_param("s", $airNo);
    $stmt->execute();
    $result = $stmt->get_result();
    $filePaths = [];

    while ($row = $result->fetch_assoc()) {
        $filePaths[] = $row['filePath'];
    }

    $stmt->close();
    $conn->close();

    if (empty($filePaths)) {
        http_response_code(404);
        echo json_encode(["error" => "No files found."]);
        exit;
    }

    // Check if all are images
    $allImages = array_filter($filePaths, function ($filePath) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
    });

    // If all are images → create a PDF with one image per page
    if (count($allImages) === count($filePaths)) {
        $pdf = new FPDF('P', 'mm', 'A4');

        foreach ($filePaths as $filePath) {
            if (!file_exists($filePath)) continue;

            list($width, $height) = getimagesize($filePath);
            $imgWidthMm = $width * 25.4 / 96;
            $imgHeightMm = $height * 25.4 / 96;

            $a4Width = 210;
            $a4Height = 297;
            $scale = min($a4Width / $imgWidthMm, $a4Height / $imgHeightMm, 1);

            $newWidth = $imgWidthMm * $scale;
            $newHeight = $imgHeightMm * $scale;

            $x = ($a4Width - $newWidth) / 2;
            $y = ($a4Height - $newHeight) / 2;

            $pdf->AddPage();
            $pdf->Image($filePath, $x, $y, $newWidth, $newHeight);
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $airNo . '_images.pdf"');
        $pdf->Output('I');
        exit;
    }

    // If only one file and it's a PDF or DOCX → return original file
    if (count($filePaths) === 1) {
        $filePath = $filePaths[0];
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($extension, ['pdf', 'docx'])) {
            $mimeType = ($extension === 'pdf')
                ? 'application/pdf'
                : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

            if (!file_exists($filePath)) {
                http_response_code(404);
                echo json_encode(["error" => "File not found."]);
                exit;
            }

            header('Content-Description: File Transfer');
            header("Content-Type: $mimeType");
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        }
    }

    http_response_code(415);
    echo json_encode(["error" => "Unsupported or mixed file types."]);
    exit;
} else {
    http_response_code(400);
    echo json_encode(["error" => "Missing air_no parameter"]);
}
?>