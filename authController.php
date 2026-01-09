<?php
// Force JSON output
header('Content-Type: application/json');

// Enable full error reporting (for debugging)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- PostgreSQL connection ---
$host = "dpg-d5g6o614tr6s73e42630-a.oregon-postgres.render.com";
$db   = "bms_pen_db";
$user = "bms_pen_db_user";
$pass = "PuV1lCJedCOHqq2ZRJ2DYPCPWuWC5Ux6";
$port = 5432;

$conn_string = "host=$host port=$port dbname=$db user=$user password=$pass sslmode=require";

try {
    $conn = @pg_connect($conn_string); // @ suppress warnings
    if (!$conn) {
        // Optional: just log the error but continue, since login is hardcoded
        // error_log("PostgreSQL connection failed");
    }

    // Check action parameter
    if (!isset($_GET['action'])) {
        echo json_encode(["status" => "error", "message" => "No action specified"]);
        exit;
    }

    $action = $_GET['action'];

    if ($action === "adminLogin") {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);

        if (!$data || !isset($data['username'], $data['password'])) {
            echo json_encode(["status" => "error", "message" => "Missing username or password"]);
            exit;
        }

        $username = trim($data['username']);
        $password = trim($data['password']);

        // --- HARD-CODED admin credentials ---
        $ADMIN_USERNAME = "admin";
        $ADMIN_PASSWORD = "#KapTata2026";

        // Use hardcoded login instead of PostgreSQL query
        if ($username === $ADMIN_USERNAME && $password === $ADMIN_PASSWORD) {
            session_start();
            $_SESSION['admin_logged_in'] = true;

            echo json_encode(["status" => "success", "message" => "Login successful"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid username or password"]);
        }

        exit;

    } else {
        echo json_encode(["status" => "error", "message" => "Unknown action"]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
    exit;
}


// --- Get action from URL ---
$action = $_GET['action'] ?? '';

if ($action === "register") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        echo json_encode(["message" => "No input received"]);
        exit;
    }

  // Sanitize inputs for PostgreSQL
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
$fourPs = pg_escape_string($data['fourPs'] ?? '');
$seniorCitizen = !empty($data['seniorCitizen']) ? 1 : 0;
$schoolLevels = !empty($data['schoolLevels']) ? implode(",", $data['schoolLevels']) : '';
$schoolName = pg_escape_string($data['schoolName'] ?? '');
$occupation = pg_escape_string($data['occupation'] ?? '');
$vaccinated = !empty($data['vaccinated']) ? 1 : 0;
$voter = !empty($data['voter']) ? 1 : 0;
$validIdBase64 = $data['validId'] ?? '';

  // --- Save Base64 ID as a file ---
$validIdPath = null;
if (!empty($validIdBase64)) {
    $validIdData = explode(',', $validIdBase64); // remove "data:image/png;base64," if present
    $decoded = base64_decode($validIdData[1] ?? '');
    if ($decoded) {
        $filename = uniqid('id_') . '.png';
        $validIdPath = 'uploads/' . $filename; // make sure folder "uploads" exists and is writable
        file_put_contents($validIdPath, $decoded);
    }
}


 // --- Check duplicate email ---
$checkQuery = "SELECT * FROM registrations WHERE email='$email'";
$check = pg_query($conn, $checkQuery);

if (pg_num_rows($check) > 0) {
    echo json_encode(["message" => "Email already exists"]);
    exit;
}


    // --- Insert into database ---
$sql = "INSERT INTO registrations 
(name, middlename, lastname, email, password, accountStatus, phone, age, sex, birthday, address, status, pwd, fourPs, seniorCitizen, schoolLevels, schoolName, occupation, vaccinated, voter, validId)
VALUES
('$name', '$middlename', '$lastname', '$email', '$password', 'pending', '$phone', $age, '$sex', '$birthday', '$address', '$status', '$pwd', '$fourPs', $seniorCitizen, '$schoolLevels', '$schoolName', '$occupation', $vaccinated, $voter, '$validIdPath')";

$result = pg_query($conn, $sql);

if ($result) {
    echo json_encode(["message" => "Registration request submitted"]);
} else {
    echo json_encode(["message" => "Error: " . pg_last_error($conn)]);
}

pg_close($conn);
exit;
}
    






if ($action === "getAllResidents") {
    $result = pg_query($conn, "SELECT * FROM registrations");
    $residents = [];

    while ($row = pg_fetch_assoc($result)) {
        $row['accountStatus'] = strtolower($row['accountStatus']); // para siguradong lowercase
        $residents[] = $row;
    }

    echo json_encode($residents);
    pg_close($conn);
    exit;
}

// ----------------------------
// Get pending clearance count
// ----------------------------
if ($action === "getPendingClearanceCount") {
    $result = pg_query($conn, "SELECT COUNT(*) as pendingCount FROM certificate_requests WHERE status='Pending'");
    $row = pg_fetch_assoc($result);
    echo json_encode(['pendingClearance' => intval($row['pendingCount'])]);
    exit;
}

// --- NEW: Update resident status (approve/reject) ---
if ($action === "updateStatus") {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $status = $_GET['status'] ?? '';

    if (!$id || !$status) {
        echo json_encode(["message" => "Missing id or status"]);
        exit;
    }
$status = pg_escape_string($status);
$sql = "UPDATE registrations SET accountStatus='$status' WHERE id=$id";

$result = pg_query($conn, $sql);

if ($result) {
    echo json_encode(["message" => "Resident status updated to $status"]);
} else {
    echo json_encode(["message" => "Error updating status: " . pg_last_error($conn)]);
}

pg_close($conn);
exit;

}

// --- NEW: Delete resident permanently ---
if ($action === "deleteResident") {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(["message" => "Missing id"]);
        exit;
    }

    $sql = "DELETE FROM registrations WHERE id=$id";
    $result = pg_query($conn, $sql);

    if ($result) {
        echo json_encode(["message" => "Resident deleted successfully"]);
    } else {
        echo json_encode(["message" => "Error deleting resident: " . pg_last_error($conn)]);
    }

    pg_close($conn);
    exit;
}


// --- NEW: Resident login ---
if ($action === "residentLogin") {
    $data = json_decode(file_get_contents("php://input"), true);

    $email = pg_escape_string($data['email'] ?? '');
    $password = $data['password'] ?? '';

    $result = pg_query($conn, "
        SELECT * FROM registrations 
        WHERE email='$email' AND accountStatus='approved' 
        LIMIT 1
    ");

    if (pg_num_rows($result) === 0) {
        http_response_code(401);
        echo json_encode(["message" => "Resident not found or not approved"]);
        exit;
    }

    $resident = pg_fetch_assoc($result);

    if (!password_verify($password, $resident['password'])) {
        http_response_code(401);
        echo json_encode(["message" => "Incorrect password"]);
        exit;
    }

    $token = bin2hex(random_bytes(16));

    // 🔑 RETURN ID AND EMAIL
    echo json_encode([
        "message" => "Login successful",
        "token" => $token,
        "role" => "resident",
        "user" => [
            "id" => $resident['id'],
            "email" => $resident['email']
        ]
    ]);

    exit;
}

///////// user dashboard editor ----

// --- Get resident info by email ---
if ($action === "getResident") {
    $email = pg_escape_string($_GET['email'] ?? '');
    if (!$email) {
        echo json_encode(["message" => "Missing email"]);
        exit;
    }

    $result = pg_query($conn, "SELECT * FROM registrations WHERE email='$email' AND accountStatus='approved' LIMIT 1");
    if (pg_num_rows($result) === 0) {
        echo json_encode(["message" => "Resident not found or not approved"]);
        exit;
    }

    $resident = pg_fetch_assoc($result);
    echo json_encode($resident);
    pg_close($conn);
    exit;
}

// --- Update resident ---
if ($action === "updateResident") {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$id) {
        echo json_encode(["message" => "Missing user ID"]);
        exit;
    }

  // Handle file upload
$validIdPath = null;
if (isset($_FILES['validId']) && $_FILES['validId']['error'] === 0) {
    $filename = uniqid('id_') . '_' . basename($_FILES['validId']['name']);
    $validIdPath = 'uploads/' . $filename;
    move_uploaded_file($_FILES['validId']['tmp_name'], $validIdPath);
}


    // Update query
$fields = [
    "email" => $_POST['username'] ?? '',  
    "password" => !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null,
    "name" => $_POST['fname'] ?? '',
    "middlename" => $_POST['mname'] ?? '',
    "lastname" => $_POST['lname'] ?? '',
    "phone" => $_POST['mPhone'] ?? '',
    "age" => isset($_POST['age']) ? intval($_POST['age']) : 0,
    "sex" => $_POST['sex'] ?? '',
    "birthday" => $_POST['birthday'] ?? '',
    "address" => $_POST['address'] ?? '',
    "status" => $_POST['status'] ?? '',
    "pwd" => (($_POST['pwd'] ?? 0) == 1) ? "Yes" : "No",
    "fourPs" => (($_POST['fourPs'] ?? 0) == 1) ? "Yes" : "No",
    "seniorCitizen" => (($_POST['seniorCitizen'] ?? 0) == 1) ? 1 : 0,
    "schoolLevels" => $_POST['schoolLevels'] ?? "",
    "schoolName" => $_POST['schoolName'] ?? '',
    "occupation" => $_POST['occupation'] ?? '',
    "vaccinated" => (($_POST['vaccinated'] ?? 0) == 1) ? 1 : 0,
    "voter" => (($_POST['voter'] ?? 0) == 1) ? 1 : 0,
    "blotterTheft" => (($_POST['blotter1'] ?? 'No') === 'Yes') ? "Yes" : "No",
    "blotterDisturbance" => (($_POST['blotter2'] ?? 'No') === 'Yes') ? "Yes" : "No",
    "blotterOther" => (($_POST['blotter3'] ?? 'No') === 'Yes') ? "Yes" : "No"
];


   // ✅ Optional password: only hash & add if user typed something
if (!empty($_POST['password'])) {
    $fields['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
}

 if ($validIdPath) $fields['validId'] = $validIdPath;

$set = [];
foreach ($fields as $k => $v) {
    // Skip null values (e.g., password not provided)
    if ($v === null) continue;
    $set[] = "$k='" . pg_escape_string($v) . "'";
}

$sql = "UPDATE registrations SET " . implode(",", $set) . " WHERE id=$id";

$result = pg_query($conn, $sql);

if ($result) {
    echo json_encode(["message" => "User updated successfully"]);
} else {
    echo json_encode(["message" => "Error: " . pg_last_error($conn)]);
}

pg_close($conn);
exit;

}



//////////////////////////////////////////////



if ($action === "adminGetResidents") {
    $result = pg_query($conn, "SELECT * FROM registrations WHERE accountStatus = 'approved'");
    $residents = [];

    while ($row = pg_fetch_assoc($result)) {
        $residents[] = $row;
    }

    echo json_encode($residents);
    pg_close($conn);
    exit;
}



if ($action === "adminSaveResident") {
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

    $id = intval($_POST['id'] ?? 0); // kung may ID = edit, kung wala = add

    // --- Handle file upload ---
    $validIdPath = null;
    if (isset($_FILES['validId']) && $_FILES['validId']['error'] === 0) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true); // gumawa kung wala
        $filename = uniqid('id_') . '_' . basename($_FILES['validId']['name']);
        $validIdPath = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES['validId']['tmp_name'], $validIdPath)) {
            echo json_encode(["message" => "Failed to move uploaded file"]);
            exit;
        }
    }
// --- Collect all fields ---
$fields = [
    "email" => $_POST['username'] ?? '',  // email ang ginagamit
    "password" => !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null,
    "name" => $_POST['fname'] ?? '',
    "middlename" => $_POST['mname'] ?? '',
    "lastname" => $_POST['lname'] ?? '',
    "phone" => $_POST['mPhone'] ?? '',
    "age" => isset($_POST['age']) ? intval($_POST['age']) : 0,
    "sex" => $_POST['sex'] ?? '',
    "birthday" => $_POST['birthday'] ?? '',
    "address" => $_POST['address'] ?? '',
    "status" => $_POST['status'] ?? '',
    "pwd" => (($_POST['pwd'] ?? 'No') === 'Yes') ? "Yes" : "No",
    "fourPs" => (($_POST['fourPs'] ?? 'No') === 'Yes') ? "Yes" : "No",
    "seniorCitizen" => (($_POST['seniorCitizen'] ?? '0') === '1') ? 1 : 0,
    "schoolLevels" => $_POST['schoolLevels'] ?? "",
    "schoolName" => $_POST['schoolName'] ?? '',
    "occupation" => $_POST['occupation'] ?? '',
    "vaccinated" => (($_POST['vaccinated'] ?? '0') === '1') ? 1 : 0,
    "voter" => (($_POST['voter'] ?? '0') === '1') ? 1 : 0,

    // ✅ Blotter Records
    "blotterTheft" => (($_POST['blotter1'] ?? 'No') === 'Yes') ? "Yes" : "No",
    "blotterDisturbance" => (($_POST['blotter2'] ?? 'No') === 'Yes') ? "Yes" : "No",
    "blotterOther" => (($_POST['blotter3'] ?? 'No') === 'Yes') ? "Yes" : "No"
];



    if ($validIdPath) $fields['validId'] = $validIdPath;

    // --- Add resident ---
if (!$id) {
    $columns = [];
    $placeholders = [];
    $values = [];
    $i = 1;

    foreach ($fields as $k => $v) {
        if ($v !== null) {
            $columns[] = $k;
            $placeholders[] = '$' . $i; // pg placeholders
            $values[] = $v;
            $i++;
        }
    }

    $sql = "INSERT INTO registrations (" . implode(",", $columns) . ") VALUES (" . implode(",", $placeholders) . ")";
    $result = pg_query_params($conn, $sql, $values);

    if ($result) {
        echo json_encode(["message" => "Resident added successfully"]);
    } else {
        echo json_encode(["message" => "Error: " . pg_last_error($conn)]);
    }

    pg_close($conn);
    exit;
}


// --- Edit resident ---
$set = [];
foreach ($fields as $k => $v) {
    if ($v !== null) {
        $set[] = "$k='" . pg_escape_string($v) . "'";
    }
}

$sql = "UPDATE registrations SET " . implode(",", $set) . " WHERE id=$id";
$result = pg_query($conn, $sql);

if ($result) {
    echo json_encode(["message" => "Resident updated successfully"]);
} else {
    echo json_encode(["message" => "Error: " . pg_last_error($conn)]);
}

pg_close($conn);
exit;
}


if ($action === "adminDeleteResident") {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(["message" => "Missing resident ID"]);
        exit;
    }

    $sql = "DELETE FROM registrations WHERE id=$id";
    $result = pg_query($conn, $sql);

    if ($result) {
        echo json_encode(["message" => "Resident deleted successfully"]);
    } else {
        echo json_encode(["message" => "Error deleting resident: " . pg_last_error($conn)]);
    }

    pg_close($conn);
    exit;
}





///////////////////////////////////////////////////////////////////////////////////////////////////////






$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

// ----------------------------
// Get user requests
// ----------------------------
if ($action === "getRequests") {
    $email = $_POST['email'] ?? '';
    if (!$email) { 
        echo json_encode([]); 
        exit; 
    }

    // Use pg_query_params for safe parameterized query
    $result = pg_query_params(
        $conn, 
        "SELECT * FROM certificate_requests WHERE username=$1 AND type='clearance' ORDER BY date DESC", 
        [$email]
    );

    $requests = [];
    while ($row = pg_fetch_assoc($result)) {
        $requests[] = $row;
    }

    echo json_encode($requests);
    exit;
}


// ----------------------------
// Delete request
// ----------------------------
if ($action === "deleteRequest") {
    $id = $_POST['id'] ?? 0;
    if (!$id) { 
        echo json_encode(['message' => 'Missing request ID']); 
        exit; 
    }

    // Use pg_query_params for safe parameterized query
    $result = pg_query_params(
        $conn, 
        "DELETE FROM certificate_requests WHERE id=$1", 
        [$id]
    );

    echo json_encode([
        'message' => $result ? 'Request deleted successfully' : 'Failed to delete request'
    ]);
    exit;
}


// ----------------------------
// Submit or update request
if ($action === "saveRequest") {

    $email   = $_POST['email']   ?? '';
    $purpose = $_POST['purpose'] ?? '';
    $purok   = $_POST['purok'] ?? ''; // ✅ Add purok
    $price   = isset($_POST['price']) ? floatval($_POST['price']) : null;
    $age     = isset($_POST['age']) ? intval($_POST['age']) : null;
    $id      = $_POST['id'] ?? null;
    $type    = 'clearance';

    // Validation
    if (!$email || !$purpose || !$purok || $price === null || $price < 0 || $age === null || $age <= 0) {
        echo json_encode([
            'message' => 'All fields are required. Age must be positive, price must be valid, and purok must be provided.'
        ]);
        exit;
    }

if ($id) {
    // UPDATE request
    $result = pg_query_params(
        $conn,
        "UPDATE certificate_requests 
         SET purpose=$1, price=$2, age=$3, purok=$4 
         WHERE id=$5 AND username=$6",
        [$purpose, $price, $age, $purok, $id, $email]
    );

    echo json_encode([
        'message' => $result ? "Request updated successfully" : "Failed to update request"
    ]);
} else {


 // INSERT new request
$result = pg_query_params(
    $conn,
    "INSERT INTO certificate_requests 
     (username, type, purpose, price, age, purok, status, date) 
     VALUES ($1, $2, $3, $4, $5, $6, 'Pending', NOW())",
    [$email, $type, $purpose, $price, $age, $purok]
);

echo json_encode([
    'message' => $result ? "Request submitted successfully" : "Failed to submit request"
]);

exit;

}

}

/////////

if ($action === "adminGetClearanceRequests") {
    $result = pg_query($conn, "
        SELECT cr.*, r.name, r.lastname
        FROM certificate_requests cr
        LEFT JOIN registrations r ON cr.username = r.email
        WHERE cr.type='clearance'
        ORDER BY cr.date DESC
    ");

    $data = [];
    while ($row = pg_fetch_assoc($result)) {
        $data[] = $row;
    }

    echo json_encode($data);
    exit;
}



if ($action === "adminUpdateRequest") {
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $msg = $_POST['adminMessage'] ?? '';

    if (!$id || !$status) {
        echo json_encode(["message" => "Missing fields"]);
        exit;
    }

    // PostgreSQL safe parameterized query
    $result = pg_query_params(
        $conn,
        "UPDATE certificate_requests 
         SET status=$1, adminMessage=$2 
         WHERE id=$3",
        [$status, $msg, $id]
    );

    echo json_encode([
        "message" => $result ? "Updated" : "Failed"
    ]);
    exit;
}


if ($action === "adminMarkPaid") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { 
        echo json_encode(["message" => "Missing ID"]); 
        exit; 
    }

    // PostgreSQL safe parameterized query
    $result = pg_query_params(
        $conn,
        "UPDATE certificate_requests SET paid=1 WHERE id=$1",
        [$id]
    );

    echo json_encode([
        "message" => $result ? "Marked as paid" : "Failed to mark as paid"
    ]);
    exit;
}


if ($action === "adminDeleteRequest") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { 
        echo json_encode(["message" => "Missing ID"]); 
        exit; 
    }

    // PostgreSQL safe deletion
    $result = pg_query_params($conn, "DELETE FROM certificate_requests WHERE id=$1", [$id]);

    echo json_encode(["message" => "Deleted"]);
    exit;
}




if ($action === "AdmingetResidencyRequests") {
    $result = pg_query($conn, "
        SELECT cr.*, r.name, r.lastname
        FROM certificate_requests cr
        LEFT JOIN registrations r ON cr.username = r.email
        WHERE cr.type='residency'
        ORDER BY cr.date DESC
    ");

    $data = [];
    while ($row = pg_fetch_assoc($result)) {
        $data[] = $row;
    }

    echo json_encode($data);
    exit;
}


if ($action === "saveRequest1") {

    $email   = $_POST['email']   ?? '';
    $purpose = $_POST['purpose'] ?? '';
    $age     = isset($_POST['age']) ? intval($_POST['age']) : null;
    $purok   = $_POST['purok']   ?? '';
    $bioName = $_POST['bioName'] ?? '';
    $price   = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $type    = $_POST['type'] ?? 'residency';
    $id      = $_POST['id'] ?? null;

    // Validation
    if (!$email || !$purpose || !$purok || !$bioName || $age === null || $age <= 0) {
        echo json_encode(['message' => 'All fields are required']);
        exit;
    }

    if ($id) {
        // UPDATE existing request
        $result = pg_query_params(
            $conn,
            "UPDATE certificate_requests 
             SET purpose=$1, age=$2, purok=$3, bioName=$4, price=$5, type=$6
             WHERE id=$7 AND username=$8",
            [$purpose, $age, $purok, $bioName, $price, $type, $id, $email]
        );

        echo json_encode([
            'message' => $result ? 'Request updated successfully' : 'Failed to update request'
        ]);

    } else {
        // INSERT new request
        $result = pg_query_params(
            $conn,
            "INSERT INTO certificate_requests
             (username, type, purpose, age, purok, bioName, price, status, date)
             VALUES ($1, $2, $3, $4, $5, $6, $7, 'Pending', NOW())",
            [$email, $type, $purpose, $age, $purok, $bioName, $price]
        );

        echo json_encode([
            'message' => $result ? 'Request submitted successfully' : 'Failed to submit request'
        ]);
    }

    exit;
}



if ($action === "deleteRequest1") {
    $id = $_POST['id'] ?? 0;
    if (!$id) { 
        echo json_encode(['message' => 'Missing request ID']); 
        exit; 
    }

    // PostgreSQL safe deletion
    $result = pg_query_params(
        $conn,
        "DELETE FROM certificate_requests WHERE id=$1",
        [$id]
    );

    echo json_encode([
        'message' => $result ? 'Request deleted successfully' : 'Failed to delete request'
    ]);
    exit;
}


if ($action === "getRequests1") {
    $email = $_POST['email'] ?? '';
    if (!$email) { 
        echo json_encode([]); 
        exit; 
    }

    // PostgreSQL safe parameterized query
    $result = pg_query_params(
        $conn,
        "SELECT id, username, type, purpose, purok, age, bioName, status, price, adminMessage, paid, date
         FROM certificate_requests
         WHERE username=$1 AND type='residency'
         ORDER BY date DESC",
        [$email]
    );

    $requests = [];
    while ($row = pg_fetch_assoc($result)) {
        $requests[] = $row;
    }

    echo json_encode($requests);
    exit;
}


if ($action === "adminUpdateRequest1") {
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $msg = $_POST['adminMessage'] ?? '';

    if (!$id || !$status) {
        echo json_encode(["message" => "Missing fields"]);
        exit;
    }

    // PostgreSQL safe parameterized query
    $result = pg_query_params(
        $conn,
        "UPDATE certificate_requests 
         SET status=$1, adminMessage=$2 
         WHERE id=$3",
        [$status, $msg, $id]
    );

    echo json_encode([
        "message" => $result ? "Updated" : "Failed"
    ]);
    exit;
}


if ($action === "adminMarkPaid1") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { 
        echo json_encode(["message" => "Missing ID"]); 
        exit; 
    }

    // PostgreSQL safe parameterized query
    $result = pg_query_params(
        $conn,
        "UPDATE certificate_requests SET paid=1 WHERE id=$1",
        [$id]
    );

    echo json_encode([
        "message" => $result ? "Marked as paid" : "Failed to mark as paid"
    ]);
    exit;
}


if ($action === "adminDeleteRequest1") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { 
        echo json_encode(["message" => "Missing ID"]); 
        exit; 
    }

    // PostgreSQL safe deletion
    $result = pg_query_params(
        $conn,
        "DELETE FROM certificate_requests WHERE id=$1",
        [$id]
    );

    echo json_encode(["message" => "Deleted"]);
    exit;
}





header('Content-Type: application/json');

// ----------------------------
// Get user requests
// ----------------------------
if ($action === "getRequests2") {
    $email = $_POST['email'] ?? '';
    if (!$email) {
        echo json_encode([]);
        exit;
    }

    // PostgreSQL safe parameterized query
    $result = pg_query_params(
        $conn,
        "SELECT id, purok, price, date 
         FROM certificate_requests 
         WHERE username=$1 AND type='indigency' 
         ORDER BY date DESC",
        [$email]
    );

    $requests = [];
    while ($row = pg_fetch_assoc($result)) {
        // Convert to string to avoid JS errors
        $row['purok'] = (string)($row['purok'] ?? '');
        $row['price'] = (string)($row['price'] ?? '0.00');
        $row['date']  = $row['date'] ?? '';
        $requests[] = $row;
    }

    // Ensure proper JSON header
    header('Content-Type: application/json');
    echo json_encode($requests);
    exit;
}


// ----------------------------
// Delete request
// ----------------------------
if ($action === "deleteRequest2") {
    $id = $_POST['id'] ?? 0;
    if (!$id) { 
        echo json_encode(['message' => 'Missing request ID']); 
        exit; 
    }

    // PostgreSQL safe deletion
    $result = pg_query_params(
        $conn,
        "DELETE FROM certificate_requests WHERE id=$1",
        [$id]
    );

    echo json_encode([
        'message' => $result ? 'Request deleted successfully' : 'Failed to delete request'
    ]);
    exit;
}


// ----------------------------
// Submit or update request
// ----------------------------
if ($action === "saveRequest2") {
    $email = $_POST['email'] ?? '';
    $purok = $_POST['purok'] ?? '';
    $price = $_POST['price'] ?? '';
    $id = $_POST['id'] ?? null;
    $type = 'indigency'; // default type

    // Validation
    if (!$email || !$purok || $price === '') {
        echo json_encode(['message' => 'All fields are required']);
        exit;
    }
if ($id) {
    // Update existing request
    $result = pg_query_params(
        $conn,
        "UPDATE certificate_requests 
         SET purok=$1, price=$2 
         WHERE id=$3 AND username=$4",
        [$purok, $price, $id, $email]
    );

    echo json_encode([
        'message' => $result ? "Request updated successfully" : "Failed to update request"
    ]);

} else {
    // Insert new request
    $status = 'Pending';
    $result = pg_query_params(
        $conn,
        "INSERT INTO certificate_requests (username, type, purok, price, status, date) 
         VALUES ($1, $2, $3, $4, $5, NOW())",
        [$email, $type, $purok, $price, $status]
    );

    echo json_encode([
        'message' => $result ? "Request submitted successfully" : "Failed to submit request"
    ]);
}

exit;

}

if ($action === "adminGetIndigencyRequests") {
    $result = pg_query($conn, "
        SELECT cr.*, r.name, r.lastname
        FROM certificate_requests cr
        LEFT JOIN registrations r ON cr.username = r.email
        WHERE cr.type='indigency'
        ORDER BY cr.date DESC
    ");

    $data = [];
    while ($row = pg_fetch_assoc($result)) {
        $data[] = $row;
    }

    echo json_encode($data);
    exit;
}



if ($action === "adminUpdateRequest2") {
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $msg = $_POST['adminMessage'] ?? '';

    if (!$id || !$status) {
        echo json_encode(["message" => "Missing fields"]);
        exit;
    }

    // PostgreSQL safe parameterized query
    $result = pg_query_params(
        $conn,
        "UPDATE certificate_requests 
         SET status=$1, adminMessage=$2 
         WHERE id=$3",
        [$status, $msg, $id]
    );

    echo json_encode([
        "message" => $result ? "Updated" : "Failed"
    ]);
    exit;
}

if ($action === "adminMarkPaid2") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { 
        echo json_encode(["message" => "Missing ID"]); 
        exit; 
    }

    // PostgreSQL safe parameterized query
    $result = pg_query_params(
        $conn,
        "UPDATE certificate_requests SET paid=1 WHERE id=$1",
        [$id]
    );

    echo json_encode([
        "message" => $result ? "Marked as paid" : "Failed to mark as paid"
    ]);
    exit;
}



if ($action === "adminDeleteRequest2") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { 
        echo json_encode(["message" => "Missing ID"]); 
        exit; 
    }

    // PostgreSQL safe deletion
    $result = pg_query_params(
        $conn,
        "DELETE FROM certificate_requests WHERE id=$1",
        [$id]
    );

    echo json_encode(["message" => "Deleted"]);
    exit;
}




// ----------------------------
// Business Clearance 
// ----------------------------
if ($action === "adminGetBusinessRequests") {
    $result = pg_query($conn, "
        SELECT cr.*, r.name, r.lastname
        FROM certificate_requests cr
        LEFT JOIN registrations r ON cr.username = r.email
        WHERE cr.type='business'
        ORDER BY cr.date DESC
    ");

    $data = [];
    while ($row = pg_fetch_assoc($result)) {
        $data[] = $row;
    }

    echo json_encode($data);
    exit;
}


if ($action === "saveBusinessRequest") {
    $email = $_POST['email'] ?? '';
    $purpose = $_POST['purpose'] ?? '';
    $businessType = $_POST['businessType'] ?? '';
    $businessName = $_POST['businessName'] ?? '';
    $businessAddress = $_POST['businessAddress'] ?? '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : null;
    $type = 'business';
    $id = $_POST['id'] ?? null;

    // Validation
    if (!$email || !$purpose || !$businessType || !$businessName || !$businessAddress || $price === null || $price < 0) {
        echo json_encode(['message' => 'All fields are required and price must be valid.']);
        exit;
    }

    if ($id) {
        // UPDATE existing request
        $result = pg_query_params(
            $conn,
            "UPDATE certificate_requests
             SET purpose=$1, businessType=$2, businessName=$3, businessAddress=$4, price=$5, type=$6
             WHERE id=$7",
            [$purpose, $businessType, $businessName, $businessAddress, $price, $type, $id]
        );

        echo json_encode([
            'message' => $result ? 'Business request updated successfully' : 'Failed to update request'
        ]);
        exit;

    } else {
        // INSERT new request
        $result = pg_query_params(
            $conn,
            "INSERT INTO certificate_requests 
             (username, type, purpose, businessType, businessName, businessAddress, price, status, date) 
             VALUES ($1, $2, $3, $4, $5, $6, $7, 'Pending', NOW())",
            [$email, $type, $purpose, $businessType, $businessName, $businessAddress, $price]
        );

        echo json_encode([
            'message' => $result ? 'Business request submitted successfully' : 'Failed to submit request'
        ]);
        exit;
    }
}


if ($action === "getBusinessRequests") {
    $email = $_POST['email'] ?? '';
    if (!$email) { 
        echo json_encode([]); 
        exit; 
    }

    // PostgreSQL safe parameterized query
    $result = pg_query_params(
        $conn,
        "SELECT id, username, type, purpose, businessType, businessName, businessAddress,
                price, status, adminMessage, paid, date
         FROM certificate_requests
         WHERE username=$1 AND type='business'
         ORDER BY date DESC",
        [$email]
    );

    $requests = [];
    while ($row = pg_fetch_assoc($result)) {
        $requests[] = $row;
    }

    echo json_encode($requests);
    exit;
}


if ($action === "deleteBusinessRequest") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['message' => 'Missing ID']);
        exit;
    }

    // PostgreSQL safe deletion
    $result = pg_query_params(
        $conn,
        "DELETE FROM certificate_requests WHERE id=$1",
        [$id]
    );

    echo json_encode([
        'message' => $result ? 'Request deleted successfully' : 'Failed to delete request'
    ]);
    exit;
}

if ($action === "adminUpdateBusinessRequest") {
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $msg = $_POST['adminMessage'] ?? '';

    if (!$id || !$status) {
        echo json_encode(["message" => "Missing fields"]);
        exit;
    }

    // PostgreSQL safe parameterized query
    $result = pg_query_params(
        $conn,
        "UPDATE certificate_requests 
         SET status=$1, adminMessage=$2 
         WHERE id=$3",
        [$status, $msg, $id]
    );

    echo json_encode([
        "message" => $result ? "Updated" : "Failed"
    ]);
    exit;
}


if ($action === "adminMarkBusinessPaid") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(["message" => "Missing ID"]);
        exit;
    }

    // PostgreSQL safe parameterized query
    $result = pg_query_params(
        $conn,
        "UPDATE certificate_requests SET paid=1 WHERE id=$1",
        [$id]
    );

    echo json_encode([
        "message" => $result ? "Marked as paid" : "Failed"
    ]);
    exit;
}




// ================================
// ANNOUNCEMENTS SYSTEM
// ================================

// GET ALL RESIDENTS (ADMIN SIDE) 
if ($action === "getResidents") {
    $result = pg_query($conn, "
        SELECT email, name, lastname 
        FROM registrations
        ORDER BY name ASC
    ");

    $residents = [];
    while ($row = pg_fetch_assoc($result)) {
        $residents[] = $row;
    }

    echo json_encode($residents);
    exit;
}



// SEND ANNOUNCEMENT (ADMIN)
// recipients = JSON array of emails OR ["all"]
if ($action === "sendAnnouncement") {
    $sender = "Admin";
    $message = $_POST['message'] ?? '';
    $recipients = json_decode($_POST['recipients'] ?? '[]', true);

    if (!$message || empty($recipients)) {
        echo json_encode(["message" => "Missing message or recipients"]);
        exit;
    }

    foreach ($recipients as $recipient) {
        // PostgreSQL safe insertion
        pg_query_params(
            $conn,
            "INSERT INTO announcements (sender, recipient, message, date_sent)
             VALUES ($1, $2, $3, NOW())",
            [$sender, $recipient, $message]
        );
    }

    echo json_encode(["message" => "Announcement sent successfully"]);
    exit;
}


// GET ANNOUNCEMENTS (ADMIN & USER)
if ($action === "getAnnouncements") {
    $result = pg_query($conn, "
        SELECT id, sender, recipient, message, date_sent
        FROM announcements
        ORDER BY date_sent DESC
    ");

    $announcements = [];
    while ($row = pg_fetch_assoc($result)) {
        $announcements[] = $row;
    }

    echo json_encode($announcements);
    exit;
}

// DELETE MULTIPLE ANNOUNCEMENTS (ADMIN)
if ($action === "deleteAnnouncements") {
    $ids = json_decode($_POST['ids'] ?? '[]', true);

    if (!is_array($ids) || empty($ids)) {
        echo json_encode(["message" => "No announcements selected"]);
        exit;
    }

    // PostgreSQL-safe deletion using ANY($1)
    $result = pg_query_params(
        $conn,
        "DELETE FROM announcements WHERE id = ANY($1::int[])",
        [ $ids ]  // array of integers
    );

    echo json_encode([
        "message" => $result
            ? "Selected announcements deleted"
            : "Failed to delete announcements"
    ]);
    exit;
}

// ======================
// GET ANNOUNCEMENTS
// ======================

if ($action === "getUserAnnouncements") {
    // siguraduhin may session/current user
    session_start();
    // For user-side announcements
    $user_email = $_SESSION['currentUser']['email'] ?? ''; // or username, depending on your session

    if (!$user_email) {
        echo json_encode([]);
        exit;
    }

    // Fetch announcements either sent to this user OR sent to everyone (recipient = 'all_users')
    $result = pg_query_params(
        $conn,
        "
        SELECT id, sender, recipient, message, date_sent
        FROM announcements
        WHERE recipient = $1 OR recipient = 'all_users'
        ORDER BY date_sent DESC
        ",
        [$user_email]
    );

    $announcements = [];
    while ($row = pg_fetch_assoc($result)) {
        $announcements[] = $row;
    }

    echo json_encode($announcements);
    exit;
}

///////////////////////////////////////////

// Read raw input (JSON)
$input = json_decode(file_get_contents('php://input'), true);

// Determine action
$action = $_GET['action'] ?? ($input['action'] ?? '');

// ---------------- GET FEES ----------------
if ($action === 'getCertificateFees') {
    $result = pg_query($conn, "SELECT * FROM certificate_fees LIMIT 1");

    if ($result && $row = pg_fetch_assoc($result)) {
        echo json_encode([
            'clearance'  => $row['clearance'],
            'residency'  => $row['residency'],
            'indigency'  => $row['indigency'],
            'business'   => $row['business']
        ]);
    } else {
        echo json_encode([]);
    }
    exit;
}

// ---------------- UPDATE FEES ----------------
if ($action === 'updateCertificateFees') {
    $fees = $input['fees'] ?? null;

    if ($fees) {
        $clearance = intval($fees['clearance']);
        $residency = intval($fees['residency']);
        $indigency = intval($fees['indigency']);
        $business  = intval($fees['business']);

        // PostgreSQL does NOT support LIMIT in UPDATE, so we just update all rows
        $sql = "UPDATE certificate_fees SET 
                    clearance = $1,
                    residency = $2,
                    indigency = $3,
                    business = $4";

        $result = pg_query_params(
            $conn,
            $sql,
            [$clearance, $residency, $indigency, $business]
        );

        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Fees updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update fees']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No fees data received']);
    }
    exit;
}

?>











