<?php
header('Content-Type: application/json');

// MySQL connection
$host = "127.0.0.1";
$db   = "registrations";
$user = "root";
$pass = "";
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die(json_encode(["message" => "Connection failed: " . $conn->connect_error]));
}

// Select all residents
$result = $conn->query("SELECT * FROM registrations");

$residents = [];
while ($row = $result->fetch_assoc()) {
    // Map the DB column for status to accountStatus (lowercase)
    // Adjust 'status' if your DB column is different
    $row['accountStatus'] = strtolower($row['accountStatus']); 
    $residents[] = $row;
}

echo json_encode($residents);

$conn->close();
?>
