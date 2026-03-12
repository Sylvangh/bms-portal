<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(200);

session_start();
ini_set('display_errors', 0);   // hide warnings from output
ini_set('log_errors', 1);       // log errors to file

$response = [];

try {
    $host = "aws-1-ap-south-1.pooler.supabase.com";
    $port = 6543;
    $db   = "postgres";
    $user = "postgres.wggqwjvdmxaplqydddjy";
    $pass = "#Sylvan2026supabase";

    $conn_string = "host=$host port=$port dbname=$db user=$user password=$pass sslmode=require";

    $conn = @pg_connect($conn_string);
    if (!$conn) throw new Exception("Database connection failed: " . pg_last_error());

    // Get action
    $action = $_GET['action'] ?? '';

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





