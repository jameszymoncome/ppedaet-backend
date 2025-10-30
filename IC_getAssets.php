<?php
// filepath: c:\xampp\htdocs\ppedaet-backend\IC_getAssets.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once 'db_connection.php';

$database = new Database();
$conn = $database->conn;

// ✅ Get userId from query params
$userId = isset($_GET['userId']) ? trim($_GET['userId']) : '';

if (empty($userId)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid userId.'
    ]);
    exit();
}

$sql = "
SELECT * FROM (
    /* PAR (not yet transferred or only pending transfer) */
    SELECT DISTINCT
        p.propertyNo AS itemNo,
        CONCAT(u.firstname, ' ', u.lastname) AS assigned_to,
        u.department AS department,
        p.article,
        p.description,
        p.model,
        p.serialNo AS serial_no,
        'PAR' AS document_type,
        p.parNo AS document_no,
        p.status,
        a.date_acquired
    FROM par AS p
    LEFT JOIN assets AS a
        ON a.item_no = p.propertyNo AND a.type = 'PAR'
    LEFT JOIN users AS u
        ON u.user_id = a.current_user_id
    WHERE p.status = 'Assigned'
      AND u.user_id = ?
      AND p.propertyNo NOT IN (
          SELECT ati.propertyNo
          FROM asset_transfer_items ati
          JOIN asset_transfer atf ON ati.transfer_id = atf.id
          WHERE atf.status = 'Completed' AND atf.type = 'PAR'
      )

    UNION ALL

    /* ICS (not yet transferred or only pending transfer) */
    SELECT DISTINCT
        i.inventoryNo AS itemNo,
        CONCAT(u.firstname, ' ', u.lastname) AS assigned_to,
        u.department AS department,
        i.article,
        i.description,
        i.model,
        i.serialNo AS serial_no,
        'ICS' AS document_type,
        i.icsNo AS document_no,
        i.status,
        a.date_acquired
    FROM ics AS i
    LEFT JOIN assets AS a
        ON a.item_no = i.inventoryNo AND a.type = 'ICS'
    LEFT JOIN users AS u
        ON u.user_id = a.current_user_id
    WHERE i.status = 'Assigned'
      AND u.user_id = ?
      AND i.inventoryNo NOT IN (
          SELECT ati.propertyNo
          FROM asset_transfer_items ati
          JOIN asset_transfer atf ON ati.transfer_id = atf.id
          WHERE atf.status = 'Completed' AND atf.type = 'ICS'
      )

    UNION ALL

    /* PTR (completed transfers) */
    SELECT
        MIN(ati.propertyNo) AS itemNo,
        CONCAT(u_to.firstname, ' ', u_to.lastname) AS assigned_to,
        u_to.department AS department,
        'Transferred Asset(s)' AS article,
        NULL AS description,
        NULL AS model,
        NULL AS serial_no,
        CONCAT('PTR (from ', atf.type, ')') AS document_type,
        atf.ptr_no AS document_no,
        'Assigned' AS status,
        atf.transfer_date AS date_acquired
    FROM asset_transfer atf
    JOIN asset_transfer_items ati ON ati.transfer_id = atf.id
    LEFT JOIN users u_to ON atf.to_officer = u_to.user_id
    WHERE atf.status = 'Completed'
      AND u_to.user_id = ?
    GROUP BY atf.ptr_no, u_to.user_id, u_to.department, atf.type, atf.transfer_date
) AS combined
ORDER BY combined.date_acquired DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $userId, $userId, $userId);
$stmt->execute();
$result = $stmt->get_result();

$assets = [];
while ($row = $result->fetch_assoc()) {
    $assets[] = $row;
}

echo json_encode(["success" => true, "assets" => $assets]);

$stmt->close();
$conn->close();
?>
