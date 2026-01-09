<?php

header('Content-Type: application/json');

// --- PostgreSQL connection on Render ---
$host = "dpg-d5g6o614tr6s73e42630-a.oregon-postgres.render.com"; // Render host
$db   = "bms_pen_db";       // Your database name
$user = "bms_pen_db_user";  // Render DB username
$pass = "PuV1lCJedCOHqq2ZRJ2DYPCPWuWC5Ux6"; // Render DB password
$port = 5432;               // default PostgreSQL port

// Force SSL connection
$conn_string = "host=$host port=$port dbname=$db user=$user password=$pass sslmode=require";
$conn = pg_connect($conn_string);

if (!$conn) {
    die(json_encode(["message" => "Connection failed: " . pg_last_error()]));
}

/////////////
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
//////////////////

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

$action = $_GET['action'] ?? '';

if ($action === "register") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        echo json_encode(["message" => "No input received"]);
        exit;
    }

    // --- Sanitize inputs for PostgreSQL ---
     // --- Sanitize inputs for PostgreSQL ---
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
    $seniorcitizen = !empty($data['seniorcitizen']) ? 1 : 0;
    $schoollevels = !empty($data['schoollevels']) ? implode(",", $data['schoollevels']) : '';
    $schoolname = pg_escape_string($data['schoolname'] ?? '');
    $occupation = pg_escape_string($data['occupation'] ?? '');
    $vaccinated = !empty($data['vaccinated']) ? 1 : 0;
    $voter = !empty($data['voter']) ? 1 : 0;
    $validIdBase64 = $data['validid'] ?? '';
    // --- Save Base64 ID as a file ---

    $validid = null;
    if (!empty($validIdBase64)) {
        $validIdData = explode(',', $validIdBase64); // remove "data:image/png;base64," if present
        $decoded = base64_decode($validIdData[1] ?? '');
        if ($decoded) {
            $filename = uniqid('id_') . '.png';
            $validid = 'uploads/' . $filename; // folder must exist and writable
            file_put_contents($validid, $decoded);
        }
    }

    // --- Check duplicate email ---
    $checkQuery = "SELECT * FROM registrations WHERE email='$email'";
    $check = pg_query($conn, $checkQuery);

    if (pg_num_rows($check) > 0) {
        echo json_encode(["message" => "Email already exists"]);
        exit;
    }

    // --- Insert into database (columns must match your PostgreSQL table) ---
    $sql = "INSERT INTO registrations 
        (name, middlename, lastname, email, password, accountstatus, phone, age, sex, birthday, address, status, pwd, fourps, seniorcitizen, schoollevels, schoolname, occupation, vaccinated, voter, validid)
        VALUES
        ('$name', '$middlename', '$lastname', '$email', '$password', 'pending', '$phone', $age, '$sex', '$birthday', '$address', '$status', '$pwd', '$fourps', $seniorcitizen, '$schoollevels', '$schoolname', '$occupation', $vaccinated, $voter, '$validid')";

    $result = pg_query($conn, $sql);

    if ($result) {
        echo json_encode(["message" => "Registration request submitted"]);
    } else {
        echo json_encode(["message" => "Error: " . pg_last_error($conn)]);
    }

    pg_close($conn);
    exit;
}

    
?>
