<?php
// ----------------------------
// authController.php
// ----------------------------

// ----------------------------
// 1. CORS Handling
// ----------------------------
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ----------------------------
// 2. JSON Response Setup
// ----------------------------
header('Content-Type: application/json');

// ----------------------------
// 3. Error Reporting (debugging)
// ----------------------------
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// ----------------------------
// 4. Start session
// ----------------------------
session_start();

// ----------------------------
// 5. Supabase Pooler Connection
// ----------------------------
$host = "aws-1-ap-south-1.pooler.supabase.com";
$port = 6543;
$db   = "postgres";
$user = "postgres.wggqwjvdmxaplqydddjy"; // format: postgres.project-ref
$pass = "#Sylvan2026supabase";

// Must be ONE LINE, no extra spaces/newlines
$conn_string = "host=$host port=$port dbname=$db user=$user password=$pass sslmode=require";

$conn = @pg_connect($conn_string);

if (!$conn) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . pg_last_error()
    ]);
    exit();
}

// ----------------------------
// 6. Get action from URL
// ----------------------------
$action = $_GET['action'] ?? '';
$response = [];

    // ---------------- GET PENDING ----------------
    if ($action === "getPending") {
        $query = "SELECT * FROM registrations WHERE accountStatus='pending' ORDER BY createdAt DESC";
        $result = pg_query($conn, $query);
        if (!$result) throw new Exception("Failed to fetch pending requests: " . pg_last_error($conn));
        $pending = pg_fetch_all($result) ?: []; // returns empty array if none
        echo json_encode($pending);
        exit();
    }

    // ---------------- APPROVE ----------------
    if ($action === "approve") {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) throw new Exception("ID is required");

        $query = "UPDATE registrations SET accountStatus='approved' WHERE id=$id";
        $result = pg_query($conn, $query);
        echo json_encode(["message" => $result ? "Request approved" : "Failed to approve"]);
        exit();
    }

    // ---------------- REJECT ----------------
    if ($action === "reject") {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) throw new Exception("ID is required");

        $data = json_decode(file_get_contents("php://input"), true);
        $message = pg_escape_string($data['message'] ?? '');

        $query = "UPDATE registrations SET accountStatus='rejected', adminMessage='$message' WHERE id=$id";
        $result = pg_query($conn, $query);
        echo json_encode(["message" => $result ? "Request rejected" : "Failed to reject"]);
        exit();
    }

    // ---------------- REMOVE ----------------
    if ($action === "remove") {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) throw new Exception("ID is required");

        $query = "DELETE FROM registrations WHERE id=$id";
        $result = pg_query($conn, $query);
        echo json_encode(["message" => $result ? "Request removed" : "Failed to remove"]);
        exit();
    }

    throw new Exception("Invalid action");

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    exit();
}




