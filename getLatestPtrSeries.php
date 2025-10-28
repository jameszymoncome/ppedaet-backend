<?php
// filepath: c:\Users\James Zymon Come\Documents\my-lgu-proj\backend\getLatestPtrSeries.php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once 'db_connection.php';

$database = new Database();
$conn = $database->conn;
$year = date('Y');

$sql = "SELECT MAX(ptr_no) AS latest_ptr_no FROM asset_transfer WHERE ptr_no LIKE ?";
$like = 'PTR-' . $year . '-%';
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $like);
$stmt->execute();
$stmt->bind_result($latest_ptr_no);
$stmt->fetch();
$stmt->close();

$next_series = '0001';
if ($latest_ptr_no) {
    $parts = explode('-', $latest_ptr_no);
    if (count($parts) === 3 && is_numeric($parts[2])) {
        $next_series = str_pad(intval($parts[2]) + 1, 4, '0', STR_PAD_LEFT);
    }
}

echo json_encode([
    "next_ptr_no" => 'PTR-' . $year . '-' . $next_series
]);
$conn->close();
?>