<?php
$conn = new mysqli("localhost", "u792590767_zymon123456", "Taetaeka123", "u792590767_daetppe_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully!";
?>