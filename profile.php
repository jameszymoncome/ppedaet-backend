<?php
// profile.php
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
    $conn = getDatabaseConnection();

    // Fetch user_id from GET parameter
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

    if ($user_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid user ID.'
        ]);
        exit();
    }

    $stmt = $conn->prepare("SELECT `user_id`, `lastname`, `firstname`, `middlename`, `suffix`, `email`, `contactNumber`, `username`, `role`, `department`, `position`, `created_at` FROM `users` WHERE `user_id` = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        echo json_encode([
            'success' => true,
            'data' => $user
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'User not found.'
        ]);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
