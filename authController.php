<?php
header('Content-Type: application/json');
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide PHP warnings from breaking JSON

try {
    // --- Check action parameter ---
    $action = $_GET['action'] ?? '';
    if ($action !== 'adminLogin') {
        echo json_encode(["status" => "error", "message" => "Invalid action"]);
        exit();
    }

    // --- PostgreSQL connection (optional if you need DB later) ---
    $host = "dpg-d5g6o614tr6s73e42630-a.oregon-postgres.render.com";
    $db   = "bms_pen_db";
    $user = "bms_pen_db_user";
    $pass = "PuV1lCJedCOHqq2ZRJ2DYPCPWuWC5Ux6";
    $port = 5432;

    $conn_string = "host=$host port=$port dbname=$db user=$user password=$pass sslmode=require";
    $conn = pg_connect($conn_string);
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // --- Hard-coded admin credentials ---
    $ADMIN_USERNAME = "admin";
    $ADMIN_PASSWORD = "#KapTata2026";

    // --- Get POSTed JSON ---
    $input = json_decode(file_get_contents("php://input"), true);
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');

    if (!$username || !$password) {
        echo json_encode(["status" => "error", "message" => "Username and password required"]);
        exit();
    }

    // --- Check credentials ---
    if ($username === $ADMIN_USERNAME && $password === $ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        echo json_encode(["status" => "success", "message" => "Login successful"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid username or password"]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

exit(); // Stop any further output
