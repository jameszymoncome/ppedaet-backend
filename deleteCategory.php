<?php
// ===== deleteCategory.php =====
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
    $conn = getDatabaseConnection();
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['id']) || empty($input['id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Category ID is required'
        ]);
        exit;
    }

    $categoryId = intval($input['id']);

    // Check if category exists
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM categorycode WHERE categoryID = ?");
    $checkStmt->bind_param("i", $categoryId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Category not found'
        ]);
        $checkStmt->close();
        $conn->close();
        exit;
    }
    $checkStmt->close();

    // Delete category
    $deleteStmt = $conn->prepare("DELETE FROM categorycode WHERE categoryID = ?");
    $deleteStmt->bind_param("i", $categoryId);

    if ($deleteStmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete category'
        ]);
    }

    $deleteStmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
