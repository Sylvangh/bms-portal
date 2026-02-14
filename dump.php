<?php
$host = "dpg-d5g6o614tr6s73e42630-a.oregon-postgres.render.com";
$db   = "bms_pen_db";
$user = "bms_pen_db_user";
$pass = "PuV1lCJedCOHqq2ZRJ2DYPCPWuWC5Ux6"; // palitan mo ng bagong password
$port = 5432;

$conn_string = "host=$host port=$port dbname=$db user=$user password=$pass sslmode=require";
$conn = pg_connect($conn_string);

if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

$result = pg_query($conn, "SELECT * FROM registrations");

if (!$result) {
    die("Query failed: " . pg_last_error($conn));
}

$data = pg_fetch_all($result);

header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="registrations_backup.json"');

echo json_encode($data);
?>
