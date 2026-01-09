<?php
// Force JSON output
header('Content-Type: application/json');
session_start(); // start session immediately

// Enable full error reporting (for debugging)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- PostgreSQL connection on Render ---
$host = "dpg-d5g6o614tr6s73e42630-a.oregon-postgres.render.com"; // Render host
$db   = "bms_pen_db";       // Your database name
$user = "bms_pen_db_user";  // Render DB username
$pass = "PuV1lCJedCOHqq2ZRJ2DYPCPWuWC5Ux6"; // Render DB password
$port = 5432;               // default PostgreSQL port

$conn_string = "host=$host port=$port dbname=$db user=$user password=$pass sslmode=require";
$conn = pg_connect($conn_string);

if (!$conn) {
    die(json_encode(["status" => "error", "message" => "Database connection failed: " . pg_last_error()]));
}

// --- Hard-coded admin credentials ---
$ADMIN_USERNAME = "admin";
$ADMIN_PASSWORD = "#KapTata2026";

// --- Get POSTed JSON ---
$input = json_decode(file_get_contents("php://input"), true);
$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');

// --- Check credentials ---
if ($username === $ADMIN_USERNAME && $password === $ADMIN_PASSWORD) {
    $_SESSION['admin_logged_in'] = true;
    echo json_encode([
        "status" => "success",
        "message" => "Login successful"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid username or password"
    ]);
}

// Close the connection (optional)
pg_close($conn);
?>
