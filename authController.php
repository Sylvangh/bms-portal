<?php
// ----------------------------
// CORS & Headers
// ----------------------------
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

$response = [];

try {
    // ----------------------------
    // Supabase PostgreSQL connection
    // ----------------------------
    $host = "aws-1-ap-south-1.pooler.supabase.com";
    $db   = "postgres";
    $user = "postgres.wggqwjvdmxaplqydddjy";
    $pass = "#Sylvan2026supabase";
    $port = 6543;

    $conn_string = "host=$host port=$port dbname=$db user=$user password=$pass sslmode=require";

    $conn = pg_connect($conn_string);

    if (!$conn) {
        throw new Exception("Database connection failed: " . pg_last_error());
    }

    // ----------------------------
    // Get action from URL
    // ----------------------------
    $action = $_GET['action'] ?? '';

    // ----------------------------
    // Admin login
    // ----------------------------
    if ($action === 'adminLogin') {
        $input = json_decode(file_get_contents("php://input"), true);

        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');

        if (!$username || !$password) {
            throw new Exception("Username and password are required");
        }

        // Hardcoded admin credentials (change if needed)
        $ADMIN_USERNAME = "admin";
        $ADMIN_PASSWORD = "#KapTata2026";

        if ($username === $ADMIN_USERNAME && $password === $ADMIN_PASSWORD) {
            $_SESSION['admin_logged_in'] = true;
            $response = ["status" => "success", "message" => "Login successful"];
        } else {
            $response = ["status" => "error", "message" => "Invalid username or password"];
        }
    } else {
        throw new Exception("Invalid action");
    }

} catch (Exception $e) {
    $response = ["status" => "error", "message" => $e->getMessage()];
}

echo json_encode($response);
exit();
