<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\getAssetTransferEmployees.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connection.php';

$conn = getDatabaseConnection();

$sql = "SELECT 
    u.user_id,
    CONCAT(u.firstname, ' ', u.lastname) AS fullname,
    u.department
FROM users AS u
LEFT JOIN air_items AS a 
    ON u.user_id = a.enduser_id
LEFT JOIN par AS p
    ON a.air_no = p.airNo
LEFT JOIN ics as i
    ON a.air_no = i.airNo
WHERE p.status = 'Assigned' OR i.status = 'Assigned'
GROUP BY u.user_id, fullname, u.department";

$result = $conn->query($sql);

$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = [
        "id" => $row['user_id'],
        "name" => $row['fullname'],
        "department" => $row['department']
    ];
}

echo json_encode(["employees" => $employees]);
$conn->close();
?>