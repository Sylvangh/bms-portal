<?php
$host = "127.0.0.1";        // localhost
$db   = "registrations";     // pangalan ng database mo sa XAMPP
$user = "root";              // default XAMPP MySQL user
$pass = "";                  // default XAMPP MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Connected successfully"; // optional testing
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
