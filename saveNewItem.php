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
    $endUserId = $input['endUserId'] ?? '';
    $formType = $input['formType'] ?? '';

    if ($formType === 'High') {
        $countResult = $conn->query("SELECT COUNT(*) AS total, COUNT(DISTINCT parNo) AS parCounts FROM par");
        $countRow = $countResult->fetch_assoc();
        $propertyCount = $countRow['total'] + 1;
        $parCount = $countRow['parCounts'] + 1;

        $insertedAirItems = [];
        $insertedParGroups = [];
        $generatedParNos = [];

        $currentYear = date("Y");

        foreach ($items as $item) {
            $key = $item['airNo'] . '|' . $item['airDate'] . '|' . $item['fund'] . '|';

            if (!isset($insertedAirItems[$key])) {
                $stmt = $conn->prepare("INSERT INTO air_items (air_no, air_date, fund, enduser_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $item['airNo'], $item['airDate'], $item['fund'], $endUserId);
                $stmt->execute();

                $insertedAirItems[$key] = true;
            }
        }

        foreach ($highValue as $item) {
            $groupKey = $item['airNo'] . '|' . $item['airDate'] . '|' . $item['fund'];

            if (!isset($insertedParGroups[$groupKey])) {
                $parNo = $currentYear . ' - ' . str_pad($parCount, 4, '0', STR_PAD_LEFT);
                $insertedParGroups[$groupKey] = $parNo;
                $generatedParNos[] = $parNo;
                $parCount++;
            }
            
            $propertyNo = $currentYear . ' - ' . $item['articleCode'] . ' - ' . str_pad($propertyCount, 4, '0', STR_PAD_LEFT);
            $parNo = $insertedParGroups[$groupKey];

            $stmt = $conn->prepare("INSERT INTO par(parNo, propertyNo, airNo, article, description, model, serialNo, unit, unitCost) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssd", $parNo, $propertyNo, $item['airNo'], $item['article'], $item['description'], $item['model'], $item['serialNo'], $item['unit'], $item['unitCost']);
            $stmt->execute();

            $propertyCount = $propertyCount + 1;
        }


        echo json_encode([
            "success" => true,
            "message" => "Data received",
            "received" => [
                "parNo" => $generatedParNos
            ]
        ]);
    }
    else if ($formType === 'Low') {
        $countResult = $conn->query("SELECT COUNT(*) AS total, COUNT(DISTINCT icsNo) AS icsCounts FROM ics");
        $countRow = $countResult->fetch_assoc();
        $inventoryCount = $countRow['total'] + 1;
        $icsCount = $countRow['icsCounts'] + 1;

        $insertedAirItems = [];
        $insertedIcsGroups = [];
        $generatedIcsNos = [];

        $currentYear = date("Y");

        foreach ($items as $item) {
            $key = $item['airNo'] . '|' . $item['airDate'] . '|' . $item['fund'] . '|';

            if (!isset($insertedAirItems[$key])) {
                // Insert this unique AIR info
                $stmt = $conn->prepare("INSERT INTO air_items (air_no, air_date, fund, enduser_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $item['airNo'], $item['airDate'], $item['fund'], $endUserId);
                $stmt->execute();

                $insertedAirItems[$key] = true;
            }
        }

        foreach ($lowValue as $item) {
            $groupKey = $item['airNo'] . '|' . $item['airDate'] . '|' . $item['fund'];

            if (!isset($insertedIcsGroups[$groupKey])) {
                $icsNo = $currentYear . ' - ' . str_pad($icsCount, 4, '0', STR_PAD_LEFT);
                $insertedIcsGroups[$groupKey] = $icsNo;
                $generatedIcsNos[] = $icsNo;

                $icsCount++;
            }

            $inventoryNo = $currentYear . ' - ' . $item['articleCode'] . ' - ' . str_pad($inventoryCount, 4, '0', STR_PAD_LEFT);
            $icsNo = $insertedIcsGroups[$groupKey];

            $stmt = $conn->prepare("INSERT INTO ics(icsNo, inventoryNo, airNo, article, description, model, serialNo, unit, unitCost) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssd", $icsNo, $inventoryNo, $item['airNo'], $item['article'], $item['description'], $item['model'], $item['serialNo'], $item['unit'], $item['unitCost']);
            $stmt->execute();

            $inventoryCount = $inventoryCount + 1;
        }

        echo json_encode([
            "success" => true,
            "message" => "Data received",
            "received" => [
                "icsNo" => $generatedIcsNos
            ]
        ]);
    }
    else {
        $countResult = $conn->query("SELECT COUNT(*) AS total, COUNT(DISTINCT parNo) AS parCounts FROM par");
        $countRow = $countResult->fetch_assoc();
        $propertyCount = $countRow['total'] + 1;
        $parCount = $countRow['parCounts'] + 1;

        $countResult = $conn->query("SELECT COUNT(*) AS total, COUNT(DISTINCT icsNo) AS icsCounts FROM ics");
        $countRow = $countResult->fetch_assoc();
        $inventoryCount = $countRow['total'] + 1;
        $icsCount = $countRow['icsCounts'] + 1;

        $insertedAirItems = [];
        $insertedParGroups = [];
        $insertedIcsGroups = [];
        $generatedParNos = [];
        $generatedIcsNos = [];


        $currentYear = date("Y");

        foreach ($items as $item) {
            $key = $item['airNo'] . '|' . $item['airDate'] . '|' . $item['fund'] . '|';

            if (!isset($insertedAirItems[$key])) {
                // Insert this unique AIR info
                $stmt = $conn->prepare("INSERT INTO air_items (air_no, air_date, fund, enduser_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $item['airNo'], $item['airDate'], $item['fund'], $endUserId);
                $stmt->execute();

                $insertedAirItems[$key] = true;
            }
        }

        foreach ($highValue as $item) {
            $groupKey = $item['airNo'] . '|' . $item['airDate'] . '|' . $item['fund'];

            if (!isset($insertedParGroups[$groupKey])) {
                $parNo = $currentYear . ' - ' . str_pad($parCount, 4, '0', STR_PAD_LEFT);
                $insertedParGroups[$groupKey] = $parNo;
                $generatedParNos[] = $parNo;
                $parCount++;
            }
            
            $propertyNo = $currentYear . ' - ' . $item['articleCode'] . ' - ' . str_pad($propertyCount, 4, '0', STR_PAD_LEFT);
            $parNo = $insertedParGroups[$groupKey];

            $stmt = $conn->prepare("INSERT INTO par(parNo, propertyNo, airNo, article, description, model, serialNo, unit, unitCost) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssd", $parNo, $propertyNo, $item['airNo'], $item['article'], $item['description'], $item['model'], $item['serialNo'], $item['unit'], $item['unitCost']);
            $stmt->execute();

            $propertyCount = $propertyCount + 1;
        }

        foreach ($lowValue as $item) {
            $groupKey = $item['airNo'] . '|' . $item['airDate'] . '|' . $item['fund'];

            if (!isset($insertedIcsGroups[$groupKey])) {
                $icsNo = $currentYear . ' - ' . str_pad($icsCount, 4, '0', STR_PAD_LEFT);
                $insertedIcsGroups[$groupKey] = $icsNo;
                $generatedIcsNos[] = $icsNo;

                $icsCount++;
            }

            $inventoryNo = $currentYear . ' - ' . $item['articleCode'] . ' - ' . str_pad($inventoryCount, 4, '0', STR_PAD_LEFT);
            $icsNo = $insertedIcsGroups[$groupKey];

            $stmt = $conn->prepare("INSERT INTO ics(icsNo, inventoryNo, airNo, article, description, model, serialNo, unit, unitCost) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssd", $icsNo, $inventoryNo, $item['airNo'], $item['article'], $item['description'], $item['model'], $item['serialNo'], $item['unit'], $item['unitCost']);
            $stmt->execute();

            $inventoryCount = $inventoryCount + 1;
        }

        echo json_encode([
            "success" => true,
            "message" => "Data received",
            "received" => [
                "parNo" => $generatedParNos,
                "icsNo" => $generatedIcsNos
            ]
        ]);


    }

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}