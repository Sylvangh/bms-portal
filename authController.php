<?php
header('Content-Type: application/json');
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1); // temporary for debugging

// --- PostgreSQL connection ---
$host = "dpg-d5g6o614tr6s73e42630-a.oregon-postgres.render.com";
$db   = "bms_pen_db";
$user = "bms_pen_db_user";
$pass = "PuV1lCJedCOHqq2ZRJ2DYPCPWuWC5Ux6";
$port = 5432;
$conn = pg_connect("host=$host port=$port dbname=$db user=$user password=$pass sslmode=require");

if (!$conn) {
    echo json_encode(["message" => "DB connection failed"]);
    exit;
}

// --- Get action ---
$action = $_GET['action'] ?? '';

if ($action === "register") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        echo json_encode(["message" => "No input received"]);
        exit;
    }

    // --- Sanitize inputs ---
    $name = pg_escape_string($data['name'] ?? '');
    $middlename = pg_escape_string($data['middlename'] ?? '');
    $lastname = pg_escape_string($data['lastname'] ?? '');
    $email = pg_escape_string($data['email'] ?? '');
    $password = password_hash($data['password'] ?? '', PASSWORD_BCRYPT);
    $phone = pg_escape_string($data['phone'] ?? '');
    $age = isset($data['age']) ? (int)$data['age'] : 0;
    $sex = pg_escape_string($data['sex'] ?? '');
    $birthday = pg_escape_string($data['birthday'] ?? '');
    $address = pg_escape_string($data['address'] ?? '');
    $status = pg_escape_string($data['status'] ?? '');
    $pwd = pg_escape_string($data['pwd'] ?? '');
    $fourps = pg_escape_string($data['fourps'] ?? '');
    $seniorcitizen = !empty($data['seniorcitizen']) ? 'TRUE' : 'FALSE';
    $schoollevels = !empty($data['schoollevels']) ? implode(",", $data['schoollevels']) : '';
    $schoolname = pg_escape_string($data['schoolname'] ?? '');
    $occupation = pg_escape_string($data['occupation'] ?? '');
    $vaccinated = !empty($data['vaccinated']) ? 'TRUE' : 'FALSE';
    $voter = !empty($data['voter']) ? 'TRUE' : 'FALSE';
    $validIdBase64 = $data['validid'] ?? '';

    // --- Save Base64 ID ---
    $validid = null;
    if (!empty($validIdBase64)) {
        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
        $validIdData = explode(',', $validIdBase64);
        $decoded = base64_decode($validIdData[1] ?? '');
        if ($decoded) {
            $filename = uniqid('id_') . '.png';
            $validid = 'uploads/' . $filename;
            file_put_contents($validid, $decoded);
        }
    }

    // --- Check duplicate email ---
    $check = pg_query($conn, "SELECT * FROM registrations WHERE email='$email'");
    if (!$check) {
        echo json_encode(["message" => "DB query error: " . pg_last_error($conn)]);
        exit;
    }
    if (pg_num_rows($check) > 0) {
        echo json_encode(["message" => "Email already exists"]);
        exit;
    }

    // --- Insert ---
    $sql = "INSERT INTO registrations
        (name, middlename, lastname, email, password, accountstatus, phone, age, sex, birthday, address, status, pwd, fourps, seniorcitizen, schoollevels, schoolname, occupation, vaccinated, voter, validid)
        VALUES
        ('$name', '$middlename', '$lastname', '$email', '$password', 'pending', '$phone', $age, '$sex', '$birthday', '$address', '$status', '$pwd', '$fourps', $seniorcitizen, '$schoollevels', '$schoolname', '$occupation', $vaccinated, $voter, '$validid')";

    $result = pg_query($conn, $sql);
    if ($result) {
        echo json_encode(["message" => "Registration request submitted"]);
    } else {
        echo json_encode(["message" => "DB insert error: " . pg_last_error($conn)]);
    }

    pg_close($conn);
    exit;
}

echo json_encode(["message" => "Invalid action"]);
exit;
?>
