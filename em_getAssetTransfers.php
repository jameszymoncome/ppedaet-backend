<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once 'db_connection.php';

$database = new Database();
$conn = $database->conn;

$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["error" => "Missing user_id"]);
    exit;
}

// Using a single query with GROUP_CONCAT to fetch all transfer details and their associated items.
// This is much more efficient than the original method which executed a new query for each transfer.
$sql = "
SELECT 
    t.ptr_no, 
    t.entity_name, 
    CONCAT(u_to.firstname, ' ', u_to.lastname) AS to_officer_name, 
    t.transfer_type,
    t.signed_doc AS signed_doc, 
    t.reason_for_transfer, 
    CONCAT(u_from.firstname, ' ', u_from.lastname) AS from_officer_name,
    t.from_officer AS from_officerID, 
    t.to_officer AS to_officerID, 
    u_to.department, 
    t.transfer_date, 
    t.status,
    -- Use GROUP_CONCAT to combine item details into a single string for each transfer.
    -- We use a unique delimiter (|||) to separate individual items and another (:::) to separate fields.
    GROUP_CONCAT(
        CONCAT_WS(':::', 
            ati.quantity, 
            ati.unit, 
            ati.description, 
            ati.propertyNo, 
            ati.amount, 
            ati.remarks
        ) SEPARATOR '|||'
    ) AS items_str
FROM asset_transfer AS t
LEFT JOIN users AS u_to
    ON u_to.user_id = t.to_officer
LEFT JOIN users AS u_from
    ON u_from.user_id = t.from_officer
LEFT JOIN asset_transfer_items AS ati
    ON t.id = ati.transfer_id
WHERE t.from_officer = ? OR u_to.user_id = ? 
GROUP BY t.id
ORDER BY t.transfer_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$transfers = [];

while ($row = $result->fetch_assoc()) {
    // Take the concatenated string of items
    $itemsStr = $row['items_str'];
    $items = [];
    
    // Check if there are any items for this transfer
    if ($itemsStr) {
        // Split the string into individual items using the main delimiter (|||)
        $itemParts = explode('|||', $itemsStr);
        foreach ($itemParts as $itemPart) {
            // Split each item part into its individual fields using the field delimiter (:::)
            list($quantity, $unit, $description, $propertyNo, $amount, $remarks) = explode(':::', $itemPart);
            
            // Reconstruct the item object
            $items[] = [
                "quantity" => $quantity,
                "unit" => $unit,
                "description" => $description,
                "propertyNo" => $propertyNo,
                "amount" => $amount,
                "remarks" => $remarks
            ];
        }
    }
    
    // Assign the reconstructed items array to the transfer row
    $row['items'] = $items;
    
    // Remove the temporary string field from the final output
    unset($row['items_str']);

    $transfers[] = $row;
}

echo json_encode(["transfers" => $transfers], JSON_PRETTY_PRINT);

$conn->close();