<?php
header('Content-Type: application/json');
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // hide PHP warnings from breaking JSON

try {
    // --- Check action parameter ---
    $action = $_GET['action'] ?? '';
    if ($action !== 'adminLogin') {
        echo json_encode(["status" => "error", "message" => "Invalid action"]);
        exit();
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

exit(); // ensure nothing else is output
