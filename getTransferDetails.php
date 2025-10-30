<?php
// filepath: c:\xampp\htdocs\ppedaet-backend\getTransferDetails.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once 'db_connection.php';

$ptr_no = isset($_GET['ptr_no']) ? trim($_GET['ptr_no']) : '';

if ($ptr_no === '') {
    echo json_encode(["success" => false, "message" => "Missing ptr_no"]);
    exit;
}

$database = new Database();
$conn = $database->conn;

/*
  1) Fetch transfer metadata
  2) Fetch transfer items (one row per item) with:
     - asset PTR row (if exists)
     - original par/ics details (article, description, model, serialNo)
*/

try {
    // 1) transfer metadata
    $metaSql = "SELECT id, ptr_no, entity_name, from_officer, to_officer, status, transfer_date, type AS origin_type
                FROM asset_transfer
                WHERE TRIM(ptr_no) = ?";
    $metaStmt = $conn->prepare($metaSql);
    $metaStmt->bind_param("s", $ptr_no);
    $metaStmt->execute();
    $metaRes = $metaStmt->get_result();

    if ($metaRes->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Transfer not found"]);
        $metaStmt->close();
        $conn->close();
        exit;
    }

    $transfer = $metaRes->fetch_assoc();
    $transfer_id = (int)$transfer['id'];
    $metaStmt->close();

    // 2) items + join to assets (PTR) + join to par/ics for original details
    $itemsSql = "
        SELECT
            TRIM(ati.propertyNo) AS propertyNo,
            -- PTR asset row if exists (type = 'PTR' and matches item_no)
            a.id AS asset_id,
            a.type AS asset_type,
            a.current_user_id AS asset_current_user_id,
            a.origin_type AS asset_origin_type,
            a.origin_user_id AS asset_origin_user_id,
            a.date_acquired AS asset_date_acquired,
            -- original tables
            p.article AS par_article, p.description AS par_description, p.model AS par_model, p.serialNo AS par_serial,
            i.article AS ics_article, i.description AS ics_description, i.model AS ics_model, i.serialNo AS ics_serial,
            -- user names
            CONCAT(u_from.firstname, ' ', u_from.lastname) AS from_name,
            CONCAT(u_to.firstname, ' ', u_to.lastname) AS to_name
        FROM asset_transfer_items ati
        LEFT JOIN assets a
            ON TRIM(a.item_no) = TRIM(ati.propertyNo) AND a.type = 'PTR'
        LEFT JOIN par p
            ON TRIM(ati.propertyNo) = TRIM(p.propertyNo)
        LEFT JOIN ics i
            ON TRIM(ati.propertyNo) = TRIM(i.inventoryNo)
        LEFT JOIN asset_transfer atf
            ON ati.transfer_id = atf.id
        LEFT JOIN users u_from ON atf.from_officer = u_from.user_id
        LEFT JOIN users u_to   ON atf.to_officer   = u_to.user_id
        WHERE ati.transfer_id = ?
        ORDER BY ati.id ASC
    ";

    $itemsStmt = $conn->prepare($itemsSql);
    $itemsStmt->bind_param("i", $transfer_id);
    $itemsStmt->execute();
    $itemsRes = $itemsStmt->get_result();

    $items = [];
    while ($r = $itemsRes->fetch_assoc()) {
        // prefer PAR fields if origin_type is PAR, else ICS, else fallback to whichever exists
        $article = null;
        $description = null;
        $model = null;
        $serial_no = null;

        // If asset_origin_type exists and equals PAR/ICS prefer that, else use whichever table has data
        $asset_origin = $r['asset_origin_type'] ?? null;

        if ($asset_origin === 'PAR') {
            $article = $r['par_article'];
            $description = $r['par_description'];
            $model = $r['par_model'];
            $serial_no = $r['par_serial'];
        } elseif ($asset_origin === 'ICS') {
            $article = $r['ics_article'];
            $description = $r['ics_description'];
            $model = $r['ics_model'];
            $serial_no = $r['ics_serial'];
        } else {
            // fallback
            $article = $r['par_article'] ?? $r['ics_article'];
            $description = $r['par_description'] ?? $r['ics_description'];
            $model = $r['par_model'] ?? $r['ics_model'];
            $serial_no = $r['par_serial'] ?? $r['ics_serial'];
        }

        $items[] = [
            "propertyNo" => $r['propertyNo'],
            "article" => $article,
            "description" => $description,
            "model" => $model,
            "serial_no" => $serial_no,
            "asset_id" => $r['asset_id'],
            "asset_type" => $r['asset_type'],
            "asset_current_user_id" => $r['asset_current_user_id'],
            "asset_origin_type" => $r['asset_origin_type'],
            "asset_origin_user_id" => $r['asset_origin_user_id'],
            "asset_date_acquired" => $r['asset_date_acquired'],
            "from_name" => $r['from_name'],
            "to_name" => $r['to_name'],
        ];
    }
    $itemsStmt->close();

    // Return transfer metadata + items + itemCount
    echo json_encode([
        "success" => true,
        "transfer" => $transfer,
        "itemCount" => count($items),
        "items" => $items
    ]);
    $conn->close();
    exit;

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    $conn->close();
    exit;
}
