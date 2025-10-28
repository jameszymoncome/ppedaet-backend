<?php
// ===== addCategory.php =====
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connection.php';

try {
    $database = new Database();
    $conn = $database->conn;
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    if (!isset($input['id']) || empty(trim($input['id']))) {
        echo json_encode([
            'success' => false,
            'message' => 'Category ID is required'
        ]);
        exit;
    }

    if (!isset($input['name']) || empty(trim($input['name']))) {
        echo json_encode([
            'success' => false,
            'message' => 'Category name is required'
        ]);
        exit;
    }

    $categoryId = trim($input['id']);
    $categoryName = trim($input['name']);

    // Validate category name length
    if (strlen($categoryName) < 2) {
        echo json_encode([
            'success' => false,
            'message' => 'Category name must be at least 2 characters long'
        ]);
        exit;
    }

    // Check if category ID already exists
    $checkIdStmt = $conn->prepare("SELECT COUNT(*) as count FROM categorycode WHERE categoryID = ?");
    $checkIdStmt->bind_param("s", $categoryId);
    $checkIdStmt->execute();
    $checkIdResult = $checkIdStmt->get_result();
    $idRow = $checkIdResult->fetch_assoc();

    if ($idRow['count'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Category ID already exists'
        ]);
        exit;
    }

    // Check if category name already exists
    $checkNameStmt = $conn->prepare("SELECT COUNT(*) as count FROM categorycode WHERE categoryName = ?");
    $checkNameStmt->bind_param("s", $categoryName);
    $checkNameStmt->execute();
    $checkNameResult = $checkNameStmt->get_result();
    $nameRow = $checkNameResult->fetch_assoc();

    if ($nameRow['count'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Category name already exists'
        ]);
        exit;
    }

    // Insert new category (assuming 'Active' as default status)
    $insertStmt = $conn->prepare("INSERT INTO categorycode (categoryID, categoryName) VALUES (?, ?)");
    $insertStmt->bind_param("ss", $categoryId, $categoryName);

    if ($insertStmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Category added successfully',
            'categoryId' => $categoryId
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to add category'
        ]);
    }

    $insertStmt->close();
    $checkIdStmt->close();
    $checkNameStmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>