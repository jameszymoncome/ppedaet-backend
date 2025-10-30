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
    /* PAR assets assigned to this user */
    SELECT 
        p.article,
        p.description,
        p.model,
        p.serialNo,
        p.unit,
        p.unitCost,
        p.propertyNo AS item_no,
        'PAR' AS type
    FROM par AS p
    LEFT JOIN air_items AS ai ON ai.air_no = p.airNo
    WHERE p.status = 'Assigned' AND ai.enduser_id = ?

    UNION ALL

    /* ICS assets assigned to this user */
    SELECT 
        i.article,
        i.description,
        i.model,
        i.serialNo,
        i.unit,
        i.unitCost,
        i.inventoryNo AS item_no,
        'ICS' AS type
    FROM ics AS i
    LEFT JOIN air_items AS ai ON ai.air_no = i.airNo
    WHERE i.status = 'Assigned' AND ai.enduser_id = ?

    UNION ALL

    /* PTR (Transferred assets assigned to this user) */
    SELECT 
        COALESCE(p.article, i.article) AS article,
        COALESCE(p.description, i.description) AS description,
        COALESCE(p.model, i.model) AS model,
        COALESCE(p.serialNo, i.serialNo) AS serialNo,
        COALESCE(p.unit, i.unit) AS unit,
        COALESCE(p.unitCost, i.unitCost) AS unitCost,
        a.item_no,
        'PTR' AS type
    FROM assets AS a
    LEFT JOIN par p ON a.origin_type = 'PAR' AND a.item_no = p.propertyNo
    LEFT JOIN ics i ON a.origin_type = 'ICS' AND a.item_no = i.inventoryNo
    WHERE (a.type = 'PTR' OR a.type = 'ptr')
      AND a.current_user_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$assets = [];
while ($row = $result->fetch_assoc()) {
    $assets[] = [
        'article' => $row['article'],
        'description' => $row['description'],
        'model' => $row['model'],
        'serialNo' => $row['serialNo'],
        'unit' => $row['unit'],
        'unitCost' => $row['unitCost'],
        'item_no' => $row['item_no'],
        'type' => $row['type']
    ];
}

echo json_encode([
    "success" => !empty($assets),
    "assets" => $assets,
    "user_id" => $user_id,
    "message" => empty($assets) ? "No assigned assets found for this user." : null
]);

$conn->close();
?>
