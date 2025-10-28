<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_connection.php';

try {
    $database = new Database();
    $conn = $database->conn;

    $item_no = $_GET['selectedItem'] ?? '';
    $selectedType = $_GET['selectedType'] ?? '';

    $sql = "SELECT
                par.propertyNo AS itemsNo,
                par.description AS name,
                par.model,
                par.article,
                assets.inspection_status AS conditions,
                par.unitCost,
                users.department,
                CONCAT(users.firstname, ' ', users.middlename, ' ', users.lastname) AS employee,
                DATE(air_items.created_at) AS acquisitionDate,
                par.serialNo,
                ih.tagID,
                ih.conditions AS inspection_condition,
                ih.remarks,
                ih.updates,
                ih.dateInspected
            FROM assets
            JOIN par ON par.propertyNo = assets.item_no AND par.type = assets.type
            INNER JOIN users ON users.user_id = assets.current_user_id
            LEFT JOIN air_items ON air_items.air_no = par.airNo
            LEFT JOIN inspectionhistory ih ON ih.tagID = par.tagID
            WHERE par.propertyNo = ? AND par.type = ?
            
            UNION ALL
            
            SELECT
                ics.inventoryNo AS itemsNo,
                ics.description AS name,
                ics.model,
                ics.article,
                assets.inspection_status AS conditions,
                ics.unitCost,
                users.department,
                CONCAT(users.firstname, ' ', users.middlename, ' ', users.lastname) AS employee,
                DATE(air_items.created_at) AS acquisitionDate,
                ics.serialNo,
                ih.tagID,
                ih.conditions AS inspection_condition,
                ih.remarks,
                ih.updates,
                ih.dateInspected
            FROM assets
            JOIN ics ON ics.inventoryNo = assets.item_no AND ics.type = assets.type
            INNER JOIN users ON users.user_id = assets.current_user_id
            LEFT JOIN air_items ON air_items.air_no = ics.airNo
            LEFT JOIN inspectionhistory ih ON ih.tagID = ics.tagID
            WHERE ics.inventoryNo = ? AND ics.type = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $item_no, $selectedType, $item_no, $selectedType);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $rows = [];
        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
        }

        // Base asset info (first row)
        $base = $rows[0];
        $asset = [
            'itemsNo' => $base['itemsNo'],
            'name' => $base['name'],
            'model' => $base['model'],
            'article' => $base['article'],
            'conditions' => $base['conditions'],
            'unitCost' => $base['unitCost'],
            'department' => $base['department'],
            'employee' => $base['employee'],
            'acquisitionDate' => $base['acquisitionDate'],
            'serialNo' => $base['serialNo'],
            'inspection_history' => []
        ];

        // Build inspection history array
        foreach ($rows as $row) {
            if (!empty($row['tagID'])) {
                $asset['inspections'][] = [
                    'tagID' => $row['tagID'],
                    'inspection_condition' => $row['inspection_condition'],
                    'remarks' => $row['remarks'],
                    'updates' => $row['updates'],
                    'dateInspected' => $row['dateInspected']
                ];
            }
        }

        echo json_encode(['success' => true, 'data' => $asset]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No data found.']);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
