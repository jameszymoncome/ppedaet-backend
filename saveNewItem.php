<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\saveNewItem.php
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
    $conn = getDatabaseConnection();
    
    // Read and decode JSON input
    $input = json_decode(file_get_contents("php://input"), true);

    // Now you can access values from JS
    $highValue = $input['highValue'] ?? [];
    $lowValue = $input['lowValue'] ?? [];
    $items = $input['items'] ?? [];
    $endUser = $input['endUser'] ?? '';
    $formType = $input['formType'] ?? '';

    if ($formType === 'High') {
        $countResult = $conn->query("SELECT COUNT(*) AS total FROM par");
        $countRow = $countResult->fetch_assoc();
        $propertyCount = $countRow['total'] + 1;

        $insertedAirItems = [];

        $currentYear = date("Y");

        foreach ($items as $item) {
            $key = $item['airNo'] . '|' . $item['airDate'] . '|' . $item['fund'] . '|';

            if (!isset($insertedAirItems[$key])) {
                // Insert this unique AIR info
                $stmt = $conn->prepare("INSERT INTO air_items (air_no, air_date, fund) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $item['airNo'], $item['airDate'], $item['fund']);
                $stmt->execute();

                $insertedAirItems[$key] = true;
            }
        }

        foreach ($highValue as $item) {
            $propertyNo = $currentYear . ' - ' . $item['articleCode'] . ' - ' . str_pad($propertyCount, 4, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO par(propertyNo, airNo, article, description, model, serialNo, unit, unitCost) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssd", $propertyNo, $item['airNo'], $item['article'], $item['description'], $item['model'], $item['serialNo'], $item['unit'], $item['unitCost']);
            $stmt->execute();

            $propertyCount = $propertyCount + 1;
        }


        echo json_encode([
            "success" => true,
            "message" => "Data received",
            "received" => [
                "inventoryNo" => $propertyNo
            ]
        ]);
    }
    else if ($formType === 'Low') {
        $countResult = $conn->query("SELECT COUNT(*) AS total FROM ics");
        $countRow = $countResult->fetch_assoc();
        $inventoryCount = $countRow['total'] + 1;

        $insertedAirItems = [];

        $currentYear = date("Y");

        foreach ($items as $item) {
            $key = $item['airNo'] . '|' . $item['airDate'] . '|' . $item['fund'] . '|';

            if (!isset($insertedAirItems[$key])) {
                // Insert this unique AIR info
                $stmt = $conn->prepare("INSERT INTO air_items (air_no, air_date, fund) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $item['airNo'], $item['airDate'], $item['fund']);
                $stmt->execute();

                $insertedAirItems[$key] = true;
            }
        }

        foreach ($lowValue as $item) {
            $inventoryNo = $currentYear . ' - ' . $item['articleCode'] . ' - ' . str_pad($inventoryCount, 4, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO ics(inventoryNo, airNo, article, description, model, serialNo, unit, unitCost) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssd", $inventoryNo, $item['airNo'], $item['article'], $item['description'], $item['model'], $item['serialNo'], $item['unit'], $item['unitCost']);
            $stmt->execute();

            $inventoryCount = $inventoryCount + 1;
        }

        echo json_encode([
            "success" => true,
            "message" => "Data received",
            "received" => [
                "inventoryNo" => $inventoryNo
            ]
        ]);
    }
    else {
        $countResult = $conn->query("SELECT COUNT(*) AS total FROM par");
        $countRow = $countResult->fetch_assoc();
        $propertyCount = $countRow['total'] + 1;

        $countResult = $conn->query("SELECT COUNT(*) AS total FROM ics");
        $countRow = $countResult->fetch_assoc();
        $inventoryCount = $countRow['total'] + 1;

        $insertedAirItems = [];

        $currentYear = date("Y");

        foreach ($items as $item) {
            $key = $item['airNo'] . '|' . $item['airDate'] . '|' . $item['fund'] . '|';

            if (!isset($insertedAirItems[$key])) {
                // Insert this unique AIR info
                $stmt = $conn->prepare("INSERT INTO air_items (air_no, air_date, fund) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $item['airNo'], $item['airDate'], $item['fund']);
                $stmt->execute();

                $insertedAirItems[$key] = true;
            }
        }

        foreach ($highValue as $item) {
            $propertyNo = $currentYear . ' - ' . $item['articleCode'] . ' - ' . str_pad($propertyCount, 4, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO par(propertyNo, airNo, article, description, model, serialNo, unit, unitCost) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssd", $propertyNo, $item['airNo'], $item['article'], $item['description'], $item['model'], $item['serialNo'], $item['unit'], $item['unitCost']);
            $stmt->execute();

            $propertyCount = $propertyCount + 1;
        }

        foreach ($lowValue as $item) {
            $inventoryNo = $currentYear . ' - ' . $item['articleCode'] . ' - ' . str_pad($inventoryCount, 4, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO ics(inventoryNo, airNo, article, description, model, serialNo, unit, unitCost) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssd", $inventoryNo, $item['airNo'], $item['article'], $item['description'], $item['model'], $item['serialNo'], $item['unit'], $item['unitCost']);
            $stmt->execute();

            $inventoryCount = $inventoryCount + 1;
        }

        echo json_encode([
            "success" => true,
            "message" => "Data received",
            "received" => [
                "parNo" => $highValue,
                "icsNo" => $lowValue,
            ]
        ]);


    }

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
