<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connection.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$database = new Database();
$conn = $database->conn;

$sql = "
    /* --- PAR assets assigned to the user --- */
    SELECT 
        p.unit,
        p.description,
        p.propertyNo,
        p.unitCost,
        a.enduser_id,
        'PAR' AS type,
        NULL AS ptr_no,
        NULL AS transfer_date
    FROM par AS p
    LEFT JOIN air_items AS a ON a.air_no = p.airNo
    LEFT JOIN users AS u ON a.enduser_id = u.user_id
    WHERE p.status = 'Assigned' AND u.user_id = ?

    UNION ALL

    /* --- ICS assets assigned to the user --- */
    SELECT 
        i.unit,
        i.description,
        i.inventoryNo AS propertyNo,
        i.unitCost,
        a.enduser_id,
        'ICS' AS type,
        NULL AS ptr_no,
        NULL AS transfer_date
    FROM ics AS i
    LEFT JOIN air_items AS a ON a.air_no = i.airNo
    LEFT JOIN users AS u ON a.enduser_id = u.user_id
    WHERE i.status = 'Assigned' AND u.user_id = ?

    UNION ALL

    /* --- PTR (Transferred) assets assigned to the user --- */
    SELECT
        COALESCE(p.unit, i.unit) AS unit,
        COALESCE(p.description, i.description) AS description,
        a.item_no AS propertyNo,
        COALESCE(p.unitCost, i.unitCost) AS unitCost,
        a.current_user_id AS enduser_id,
        'PTR' AS type,
        atf.ptr_no AS ptr_no,
        atf.transfer_date AS transfer_date
    FROM assets a
    JOIN asset_transfer_items ati ON ati.propertyNo = a.item_no
    JOIN asset_transfer atf ON ati.transfer_id = atf.id
    LEFT JOIN par p ON a.origin_type = 'PAR' AND TRIM(a.item_no) = TRIM(p.propertyNo)
    LEFT JOIN ics i ON a.origin_type = 'ICS' AND TRIM(a.item_no) = TRIM(i.inventoryNo)
    LEFT JOIN users u ON a.current_user_id = u.user_id
    WHERE a.type = 'PTR' AND a.current_user_id = ? AND atf.status = 'Completed'
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$assets = [];
while ($row = $result->fetch_assoc()) {
    $assets[] = [
        "unit" => $row['unit'],
        "description" => $row['description'],
        "propertyNo" => $row['propertyNo'],
        "unitCost" => $row['unitCost'],
        "type" => $row['type'],
        "ptr_no" => $row['ptr_no'],
        "transfer_date" => $row['transfer_date']
    ];
}

echo json_encode([
    "success" => true,
    "assets" => $assets
]);

$conn->close();
?>
