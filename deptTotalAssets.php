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

    $sql = "
        SELECT
            d.entity_name AS department,
            COUNT(u.user_id) AS employees,
            COALESCE(SUM(p.totalValue), 0) AS totalValue,
            COALESCE(SUM(p.totalAssets), 0) AS totalAssets
        FROM departmenttbl d
        LEFT JOIN users u ON u.department = d.entity_name
        LEFT JOIN (
            -- Assets from PAR
            SELECT 
                a.current_user_id,
                SUM(par.unitCost) AS totalValue,
                COUNT(par.tagID) AS totalAssets
            FROM assets a
            JOIN par 
              ON par.propertyNo = a.item_no 
             AND par.type = a.type
            GROUP BY a.current_user_id

            UNION ALL

            -- Assets from ICS
            SELECT 
                a.current_user_id,
                SUM(ics.unitCost) AS totalValue,
                COUNT(ics.tagID) AS totalAssets
            FROM assets a
            JOIN ics 
              ON ics.inventoryNo = a.item_no 
             AND ics.type = a.type
            GROUP BY a.current_user_id
        ) p ON p.current_user_id = u.user_id
        GROUP BY d.entity_name
        ORDER BY totalValue DESC
    ";

    // Prepare and execute the query
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $taggedAssets = [];
    while ($row = $result->fetch_assoc()) {
        $taggedAssets[] = $row;
    }

    echo json_encode([
        "success" => true,
        "data" => $taggedAssets
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
