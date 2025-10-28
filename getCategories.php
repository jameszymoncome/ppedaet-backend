<?php
// ===== getCategories.php =====

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connection.php';

try {
    $database = new Database();
    $conn = $database->conn;

    $query = "SELECT categoryID AS id, categoryName AS name, 'Active' AS status FROM categorycode ORDER BY categoryID DESC";
    $result = $conn->query($query);

    if (!$result) {
        echo json_encode([
            'success' => false,
            'message' => 'Query failed: ' . $conn->error
        ]);
        exit();
    }

    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }

    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'data' => $categories // for compatibility with frontend
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
