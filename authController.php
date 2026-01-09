<?php
header('Content-Type: application/json');
session_start();

// --- Hide HTML errors ---
error_reporting(E_ALL);
ini_set('display_errors', 0);

// --- PostgreSQL connection ---
$host = "dpg-d5g6o614tr6s73e42630-a.oregon-postgres.render.com";
$db   = "bms_pen_db";
$user = "bms_pen_db_user";
$pass = "PuV1lCJedCOHqq2ZRJ2DYPCPWuWC5Ux6";
$port = 5432;

$conn_string = "host=$host port=$port dbname=$db user=$user password=$pass sslmode=require";
$conn = pg_connect($conn_string);

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

// --- Get action ---
$action = $_GET['action'] ?? '';

if ($action === 'register') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) {
        echo json_encode(["status" => "error", "message" => "No input received"]);
        exit;
    }

    // --- Sanitize inputs ---
    $name = pg_escape_string(trim($data['name'] ?? ''));
    $middlename = pg_escape_string(trim($data['middlename'] ?? ''));
    $lastname = pg_escape_string(trim($data['lastname'] ?? ''));
    $email = pg_escape_string(trim($data['email'] ?? ''));
    $password_raw = trim($data['password'] ?? '');
    $password = $password_raw ? password_hash($password_raw, PASSWORD_BCRYPT) : null;
    $phone = pg_escape_string(trim($data['phone'] ?? ''));
    $age = isset($data['age']) ? (int)$data['age'] : 0;
    $sex = pg_escape_string(trim($data['sex'] ?? ''));
    $birthday = pg_escape_string(trim($data['birthday'] ?? ''));
    $address = pg_escape_string(trim($data['address'] ?? ''));
    $status = pg_escape_string(trim($data['status'] ?? ''));
    $pwd = pg_escape_string(trim($data['pwd'] ?? ''));
    $fourps = pg_escape_string(trim($data['fourps'] ?? ''));
    $seniorcitizen = !empty($data['seniorcitizen']) ? 1 : 0;
    $schoollevels = !empty($data['schoollevels']) ? implode(",", $data['schoollevels']) : '';
    $schoolname = pg_escape_string(trim($data['schoolname'] ?? ''));
    $occupation = pg_escape_string(trim($data['occupation'] ?? ''));
    $vaccinated = !empty($data['vaccinated']) ? 1 : 0;
    $voter = !empty($data['voter']) ? 1 : 0;
    $validIdBase64 = $data['validid'] ?? '';

    // --- Validate required fields ---
    if (!$name || !$lastname || !$email || !$password_raw) {
        echo json_encode(["status" => "error", "message" => "Name, Lastname, Email, and Password are required"]);
        exit;
    }

    // --- Save Base64 ID as file ---
    $validid = null;
    if (!empty($validIdBase64)) {
        $parts = explode(',', $validIdBase64);
        if (isset($parts[1])) {
            $decoded = base64_decode($parts[1]);
            if ($decoded) {
                $filename = uniqid('id_') . '.png';
                $validid = 'uploads/' . $filename;
                if (!file_put_contents($validid, $decoded)) {
                    echo json_encode(["status" => "error", "message" => "Failed to save ID image"]);
                    exit;
                }
            } else {
                echo json_encode(["status" => "error", "message" => "Invalid Base64 ID data"]);
                exit;
            }
        }
    }

    // --- Check duplicate email ---
    $checkQuery = "SELECT id FROM registrations WHERE email='$email'";
    $check = pg_query($conn, $checkQuery);
    if (!$check) {
        echo json_encode(["status" => "error", "message" => "Database error: " . pg_last_error($conn)]);
        exit;
    }
    if (pg_num_rows($check) > 0) {
        echo json_encode(["status" => "error", "message" => "Email already exists"]);
        exit;
    }

    // --- Insert into database ---
    $sql = "INSERT INTO registrations 
        (name, middlename, lastname, email, password, accountstatus, phone, age, sex, birthday, address, status, pwd, fourps, seniorcitizen, schoollevels, schoolname, occupation, vaccinated, voter, validid)
        VALUES
        ('$name', '$middlename', '$lastname', '$email', '$password', 'pending', '$phone', $age, '$sex', '$birthday', '$address', '$status', '$pwd', '$fourps', $seniorcitizen, '$schoollevels', '$schoolname', '$occupation', $vaccinated, $voter, '$validid')";

    $result = pg_query($conn, $sql);

    if ($result) {
        echo json_encode(["status" => "success", "message" => "Registration request submitted"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database insert error: " . pg_last_error($conn)]);
    }

    pg_close($conn);
    exit;
}

// --- Invalid action fallback ---
echo json_encode(["status" => "error", "message" => "Invalid action"]);
exit;
?>
