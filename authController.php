<?php
header('Content-Type: application/json');

// --- MySQL connection for XAMPP ---
$host = "127.0.0.1";
$db   = "registrations"; // palitan ng actual database name mo
$user = "root";
$pass = "";             // default sa XAMPP
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die(json_encode(["message" => "Connection failed: " . $conn->connect_error]));
}

// --- Get action from URL ---
$action = $_GET['action'] ?? '';

if ($action === "register") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        echo json_encode(["message" => "No input received"]);
        exit;
    }

    // Sanitize inputs
    $name = $conn->real_escape_string($data['name']);
    $middlename = $conn->real_escape_string($data['middlename']);
    $lastname = $conn->real_escape_string($data['lastname']);
    $email = $conn->real_escape_string($data['email']);
    $password = password_hash($data['password'], PASSWORD_BCRYPT);
    $phone = $conn->real_escape_string($data['phone']);
    $age = (int)$data['age'];
    $sex = $conn->real_escape_string($data['sex']);
    $birthday = $conn->real_escape_string($data['birthday']);
    $address = $conn->real_escape_string($data['address']);
    $status = $conn->real_escape_string($data['status']);
    $pwd = $conn->real_escape_string($data['pwd']);
    $fourPs = $conn->real_escape_string($data['fourPs']);
    $seniorCitizen = $data['seniorCitizen'] ? 1 : 0;
    $schoolLevels = implode(",", $data['schoolLevels']);
    $schoolName = $conn->real_escape_string($data['schoolName']);
    $occupation = $conn->real_escape_string($data['occupation']);
    $vaccinated = $data['vaccinated'] ? 1 : 0;
    $voter = $data['voter'] ? 1 : 0;
    $validIdBase64 = $data['validId'] ?? '';

    // --- Save Base64 ID as a file ---
    $validIdPath = null;
    if ($validIdBase64) {
        $validIdData = explode(',', $validIdBase64); // remove "data:image/png;base64,"
        $decoded = base64_decode($validIdData[1] ?? '');
        if ($decoded) {
            $filename = uniqid('id_') . '.png';
            $validIdPath = 'uploads/' . $filename; // make sure folder "uploads" exists
            file_put_contents($validIdPath, $decoded);
        }
    }

    // --- Check duplicate email ---
    $check = $conn->query("SELECT * FROM registrations WHERE email='$email'");
    if ($check->num_rows > 0) {
        echo json_encode(["message" => "Email already exists"]);
        exit;
    }

    // --- Insert into database ---
$sql = "INSERT INTO registrations 
(name, middlename, lastname, email, password, accountStatus, phone, age, sex, birthday, address, status, pwd, fourPs, seniorCitizen, schoolLevels, schoolName, occupation, vaccinated, voter, validId)
VALUES
('$name', '$middlename', '$lastname', '$email', '$password', 'pending', '$phone', $age, '$sex', '$birthday', '$address', '$status', '$pwd', '$fourPs', $seniorCitizen, '$schoolLevels', '$schoolName', '$occupation', $vaccinated, $voter, '$validIdPath')";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(["message" => "Registration request submitted"]);
    } else {
        echo json_encode(["message" => "Error: " . $conn->error]);
    }

    $conn->close();
    exit;  

}

if ($action === "adminLogin") {
    $data = json_decode(file_get_contents("php://input"), true);

    $username = $data['username'];
    $password = $data['password'];

    // HARD CODED CREDENTIALS
    $ADMIN_USERNAME = "admin";
    $ADMIN_PASSWORD = "#KapTata2026";

    if ($username === $ADMIN_USERNAME && $password === $ADMIN_PASSWORD) {

        session_start();
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
    exit;
}



if ($action === "getAllResidents") {
    $result = $conn->query("SELECT * FROM registrations");
    $residents = [];
    while($row = $result->fetch_assoc()) {
        $row['accountStatus'] = strtolower($row['accountStatus']); // para siguradong lowercase
        $residents[] = $row;
    }
    echo json_encode($residents);
    $conn->close();
    exit;
}

// ----------------------------
// Get pending clearance count
// ----------------------------
if ($action === "getPendingClearanceCount") {
    $result = $conn->query("SELECT COUNT(*) as pendingCount FROM certificate_requests WHERE status='Pending'");
    $row = $result->fetch_assoc();
    echo json_encode(['pendingClearance' => intval($row['pendingCount'])]);
    exit;
}


// --- NEW: Update resident status (approve/reject) ---
if ($action === "updateStatus") {
    $id = intval($_GET['id'] ?? 0);
    $status = $_GET['status'] ?? '';

    if (!$id || !$status) {
        echo json_encode(["message" => "Missing id or status"]);
        exit;
    }

    $status = $conn->real_escape_string($status);
    $sql = "UPDATE registrations SET accountStatus='$status' WHERE id=$id";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(["message" => "Resident status updated to $status"]);
    } else {
        echo json_encode(["message" => "Error updating status: ".$conn->error]);
    }
    $conn->close();
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
    if ($conn->query($sql) === TRUE) {
        echo json_encode(["message" => "Resident deleted successfully"]);
    } else {
        echo json_encode(["message" => "Error deleting resident: ".$conn->error]);
    }
    $conn->close();
    exit;
}

// --- NEW: Resident login ---
if ($action === "residentLogin") {
    $data = json_decode(file_get_contents("php://input"), true);

    $email = $conn->real_escape_string($data['email'] ?? '');
    $password = $data['password'] ?? '';

    $result = $conn->query("
        SELECT * FROM registrations 
        WHERE email='$email' AND accountStatus='approved' 
        LIMIT 1
    ");

    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(["message" => "Resident not found or not approved"]);
        exit;
    }

    $resident = $result->fetch_assoc();

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
    $email = $conn->real_escape_string($_GET['email'] ?? '');
    if (!$email) {
        echo json_encode(["message"=>"Missing email"]);
        exit;
    }

    $result = $conn->query("SELECT * FROM registrations WHERE email='$email' AND accountStatus='approved' LIMIT 1");
    if ($result->num_rows === 0) {
        echo json_encode(["message"=>"Resident not found or not approved"]);
        exit;
    }

    $resident = $result->fetch_assoc();
    echo json_encode($resident);
    $conn->close();
    exit;
}

// --- Update resident ---
if ($action === "updateResident") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(["message" => "Missing user ID"]);
        exit;
    }

    // Handle file upload
    $validIdPath = null;
    if (isset($_FILES['validId']) && $_FILES['validId']['error'] === 0) {
        $filename = uniqid('id_') . '_' . $_FILES['validId']['name'];
        $validIdPath = 'uploads/' . $filename;
        move_uploaded_file($_FILES['validId']['tmp_name'], $validIdPath);
    }

    // Update query
$fields = [
    "email" => $_POST['username'],  
    "password" => !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null,
    "name" => $_POST['fname'],
    "middlename" => $_POST['mname'],
    "lastname" => $_POST['lname'],
    "phone" => $_POST['mPhone'],
    "age" => intval($_POST['age']),
    "sex" => $_POST['sex'],
    "birthday" => $_POST['birthday'],
    "address" => $_POST['address'],
    "status" => $_POST['status'],
    "pwd" => ($_POST['pwd'] ?? 0) == 1 ? "Yes" : "No",
    "fourPs" => ($_POST['fourPs'] ?? 0) == 1 ? "Yes" : "No",
    "seniorCitizen" => ($_POST['seniorCitizen'] ?? 0) == 1 ? 1 : 0,
    "schoolLevels" => $_POST['schoolLevels'] ?? "",
    "schoolName" => $_POST['schoolName'],
    "occupation" => $_POST['occupation'],
    "vaccinated" => ($_POST['vaccinated'] ?? 0) == 1 ? 1 : 0,
    "voter" => ($_POST['voter'] ?? 0) == 1 ? 1 : 0,
    "blotterTheft" => ($_POST['blotter1'] ?? 'No') === 'Yes' ? "Yes" : "No",
    "blotterDisturbance" => ($_POST['blotter2'] ?? 'No') === 'Yes' ? "Yes" : "No",
    "blotterOther" => ($_POST['blotter3'] ?? 'No') === 'Yes' ? "Yes" : "No"
];

        // ✅ Optional password: only hash & add if user typed something
    if (!empty($_POST['password'])) {
        $fields['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
    }

    if ($validIdPath) $fields['validId'] = $validIdPath;

    $set = [];
    foreach ($fields as $k => $v) {
        $set[] = "$k='" . $conn->real_escape_string($v) . "'";
    }

    $sql = "UPDATE registrations SET " . implode(",", $set) . " WHERE id=$id";

    if ($conn->query($sql)) {
        echo json_encode(["message" => "User updated successfully"]);
    } else {
        echo json_encode(["message" => "Error: " . $conn->error]);
    }

    $conn->close();
    exit;
}



//////////////////////////////////////////////



if ($action === "adminGetResidents") {
    $result = $conn->query("SELECT * FROM registrations WHERE accountStatus = 'approved'");
    $residents = [];
    while($row = $result->fetch_assoc()) {
        $residents[] = $row;
    }
    echo json_encode($residents);
    $conn->close();
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
    "email" => $_POST['username'],  // email ang ginagamit
    "password" => !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null,
    "name" => $_POST['fname'],
    "middlename" => $_POST['mname'],
    "lastname" => $_POST['lname'],
    "phone" => $_POST['mPhone'],
    "age" => intval($_POST['age']),
    "sex" => $_POST['sex'],
    "birthday" => $_POST['birthday'],
    "address" => $_POST['address'],
    "status" => $_POST['status'],
    "pwd" => ($_POST['pwd'] ?? 'No') === 'Yes' ? "Yes" : "No",
    "fourPs" => ($_POST['fourPs'] ?? 'No') === 'Yes' ? "Yes" : "No",
    "seniorCitizen" => ($_POST['seniorCitizen'] ?? '0') === '1' ? 1 : 0,
    "schoolLevels" => $_POST['schoolLevels'] ?? "",
    "schoolName" => $_POST['schoolName'],
    "occupation" => $_POST['occupation'],
    "vaccinated" => ($_POST['vaccinated'] ?? '0') === '1' ? 1 : 0,
    "voter" => ($_POST['voter'] ?? '0') === '1' ? 1 : 0,

       // ✅ Blotter Records
    "blotterTheft" => ($_POST['blotter1'] ?? 'No') === 'Yes' ? "Yes" : "No",
    "blotterDisturbance" => ($_POST['blotter2'] ?? 'No') === 'Yes' ? "Yes" : "No",
    "blotterOther" => ($_POST['blotter3'] ?? 'No') === 'Yes' ? "Yes" : "No"
];


    if ($validIdPath) $fields['validId'] = $validIdPath;

    // --- Add resident ---
    if (!$id) {
        $columns = [];
        $values = [];
        foreach ($fields as $k => $v) {
            if ($v !== null) {
                $columns[] = $k;
                $values[] = "'" . $conn->real_escape_string($v) . "'";
            }
        }
        $sql = "INSERT INTO registrations (" . implode(",", $columns) . ") VALUES (" . implode(",", $values) . ")";
        if ($conn->query($sql)) {
            echo json_encode(["message" => "Resident added successfully"]);
        } else {
            echo json_encode(["message" => "Error: " . $conn->error]);
        }
        $conn->close();
        exit;
    }

    // --- Edit resident ---
    $set = [];
    foreach ($fields as $k => $v) {
        if ($v !== null) {
            $set[] = "$k='" . $conn->real_escape_string($v) . "'";
        }
    }
    $sql = "UPDATE registrations SET " . implode(",", $set) . " WHERE id=$id";
    if ($conn->query($sql)) {
        echo json_encode(["message" => "Resident updated successfully"]);
    } else {
        echo json_encode(["message" => "Error: " . $conn->error]);
    }
    $conn->close();
    exit;
}


if ($action === "adminDeleteResident") {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(["message" => "Missing resident ID"]);
        exit;
    }

    $sql = "DELETE FROM registrations WHERE id=$id";
    if ($conn->query($sql)) {
        echo json_encode(["message" => "Resident deleted successfully"]);
    } else {
        echo json_encode(["message" => "Error deleting resident: " . $conn->error]);
    }
    $conn->close();
    exit;

}




///////////////////////////////////////////////////////////////////////////////////////////////////////






// Assume $conn is your MySQLi connection
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

// ----------------------------
// Get user requests
// ----------------------------
if ($action === "getRequests") {
    $email = $_POST['email'] ?? '';
    if (!$email) { echo json_encode([]); exit; }

    $stmt = $conn->prepare("SELECT * FROM certificate_requests WHERE username=? AND type='clearance' ORDER BY date DESC");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $requests = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($requests);
    exit;
}

// ----------------------------
// Delete request
// ----------------------------
if ($action === "deleteRequest") {
    $id = $_POST['id'] ?? 0;
    if (!$id) { echo json_encode(['message' => 'Missing request ID']); exit; }

    $stmt = $conn->prepare("DELETE FROM certificate_requests WHERE id=?");
    $stmt->bind_param("i", $id);
    echo json_encode(['message' => $stmt->execute() ? 'Request deleted successfully' : 'Failed to delete request']);
    exit;
}

// ----------------------------
// Submit or update request
// ----------------------------
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
        $stmt = $conn->prepare("
            UPDATE certificate_requests 
            SET purpose=?, price=?, age=?, purok=? 
            WHERE id=? AND username=?
        ");
        // s = string, d = double, i = int
        $stmt->bind_param("sdissi", $purpose, $price, $age, $purok, $id, $email);

        $success = $stmt->execute();
        echo json_encode([
            'message' => $success ? "Request updated successfully" : "Failed to update request"
        ]);
    } else {
        // INSERT new request
        $stmt = $conn->prepare("
            INSERT INTO certificate_requests 
            (username, type, purpose, price, age, purok, status, date) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())
        ");
        $stmt->bind_param("sssdis", $email, $type, $purpose, $price, $age, $purok);

        $success = $stmt->execute();
        echo json_encode([
            'message' => $success ? "Request submitted successfully" : "Failed to submit request"
        ]);
    }

    exit;
}



/////////

if ($action === "adminGetClearanceRequests") {
    $result = $conn->query("
        SELECT cr.*, r.name, r.lastname
        FROM certificate_requests cr
        LEFT JOIN registrations r ON cr.username = r.email
        WHERE cr.type='clearance'
        ORDER BY cr.date DESC
    ");
    $data = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($data);
    exit;
}


if ($action === "adminUpdateRequest") {
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $msg = $_POST['adminMessage'] ?? '';

    if (!$id || !$status) {
        echo json_encode(["message"=>"Missing fields"]);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE certificate_requests 
        SET status=?, adminMessage=? 
        WHERE id=?
    ");
    $stmt->bind_param("ssi", $status, $msg, $id);
    echo json_encode(["message"=>$stmt->execute() ? "Updated" : "Failed"]);
    exit;
}
if ($action === "adminMarkPaid") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { 
        echo json_encode(["message"=>"Missing ID"]); 
        exit; 
    }

    $stmt = $conn->prepare("UPDATE certificate_requests SET paid=1 WHERE id=?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();

    echo json_encode(["message" => $success ? "Marked as paid" : "Failed to mark as paid"]);
    exit;
}


if ($action === "adminDeleteRequest") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(["message"=>"Missing ID"]); exit; }

    $conn->query("DELETE FROM certificate_requests WHERE id=$id");
    echo json_encode(["message"=>"Deleted"]);
    exit;
}





// ----------------------------
// Certificate of Residency Requests
// ----------------------------
/*
if ($action === "getResidencyRequests") {
    $email = $_POST['email'] ?? '';
    if (!$email) { echo json_encode([]); exit; }

    $stmt = $conn->prepare("SELECT * FROM certificate_requests WHERE username=? AND type='residency' ORDER BY date DESC");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $requests = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($requests);
    exit;
}
*/


if ($action === "AdmingetResidencyRequests") {
    $result = $conn->query("
        SELECT cr.*, r.name, r.lastname
        FROM certificate_requests cr
        LEFT JOIN registrations r ON cr.username = r.email
        WHERE cr.type='residency'
        ORDER BY cr.date DESC
    ");
    $data = $result->fetch_all(MYSQLI_ASSOC);
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
        // UPDATE
        $stmt = $conn->prepare("
            UPDATE certificate_requests 
            SET purpose=?, age=?, purok=?, bioName=?, price=?, type=?
            WHERE id=? AND username=?
        ");

        // s = string, i = int, d = double
        $stmt->bind_param(
            "sissdsis",
            $purpose,
            $age,
            $purok,
            $bioName,
            $price,
            $type,
            $id,
            $email
        );

        $success = $stmt->execute();
        echo json_encode([
            'message' => $success ? 'Request updated successfully' : 'Failed to update request'
        ]);

    } else {
        // INSERT
        $stmt = $conn->prepare("
            INSERT INTO certificate_requests
            (username, type, purpose, age, purok, bioName, price, status, date)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
        ");

        $stmt->bind_param(
            "sssissd",
            $email,
            $type,
            $purpose,
            $age,
            $purok,
            $bioName,
            $price
        );

        $success = $stmt->execute();
        echo json_encode([
            'message' => $success ? 'Request submitted successfully' : 'Failed to submit request'
        ]);
    }

    exit;
}


if ($action === "deleteRequest1") {
    $id = $_POST['id'] ?? 0;
    if (!$id) { echo json_encode(['message'=>'Missing request ID']); exit; }

    $stmt = $conn->prepare("DELETE FROM certificate_requests WHERE id=?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    echo json_encode(['message'=>$success ? 'Request deleted successfully' : 'Failed to delete request']);
    exit;
}

if ($action === "getRequests1") {
    $email = $_POST['email'] ?? '';
    if (!$email) { 
        echo json_encode([]); 
        exit; 
    }

    $stmt = $conn->prepare("
        SELECT id, username, type, purpose, purok, age, bioName, status,price, adminMessage, paid, date
        FROM certificate_requests
        WHERE username=? AND type='residency'
        ORDER BY date DESC
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $requests = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($requests);
    exit;
}


if ($action === "adminUpdateRequest1") {
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $msg = $_POST['adminMessage'] ?? '';

    if (!$id || !$status) {
        echo json_encode(["message"=>"Missing fields"]);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE certificate_requests 
        SET status=?, adminMessage=? 
        WHERE id=?
    ");
    $stmt->bind_param("ssi", $status, $msg, $id);
    echo json_encode(["message"=>$stmt->execute() ? "Updated" : "Failed"]);
    exit;
}

if ($action === "adminMarkPaid1") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { 
        echo json_encode(["message"=>"Missing ID"]); 
        exit; 
    }

    $stmt = $conn->prepare("UPDATE certificate_requests SET paid=1 WHERE id=?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();

    echo json_encode(["message" => $success ? "Marked as paid" : "Failed to mark as paid"]);
    exit;
}

if ($action === "adminDeleteRequest1") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(["message"=>"Missing ID"]); exit; }

    $conn->query("DELETE FROM certificate_requests WHERE id=$id");
    echo json_encode(["message"=>"Deleted"]);
    exit;
}






/////////indigency

// Assume $conn is your MySQLi connection

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

    // Select only the columns you need
    $stmt = $conn->prepare("SELECT id, purok, price, date FROM certificate_requests WHERE username=? AND type='indigency' ORDER BY date DESC");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    $requests = [];
    while ($row = $result->fetch_assoc()) {
        // Convert to string to avoid JS errors
        $row['purok'] = (string)($row['purok'] ?? '');
        $row['price'] = (string)($row['price'] ?? '0.00');
        $row['date'] = $row['date'] ?? '';
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
    if (!$id) { echo json_encode(['message' => 'Missing request ID']); exit; }

    $stmt = $conn->prepare("DELETE FROM certificate_requests WHERE id=?");
    $stmt->bind_param("i", $id);
    echo json_encode(['message' => $stmt->execute() ? 'Request deleted successfully' : 'Failed to delete request']);
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

    // Validate required fields
    if (!$email || !$purok || $price === '') {
        echo json_encode(['message' => 'Missing fields']);
        exit;
    }

    if ($id) {
        // Update existing request
        $stmt = $conn->prepare("UPDATE certificate_requests SET purok=?, price=? WHERE id=? AND username=?");
        $stmt->bind_param("sdis", $purok, $price, $id, $email); // s=string, d=decimal, i=integer
        $success = $stmt->execute();
        echo json_encode(['message' => $success ? "Request updated successfully" : "Failed to update request"]);
    } else {
        // Insert new request
        $status = 'Pending';
        $stmt = $conn->prepare("INSERT INTO certificate_requests (username, type, purok, price, status, date) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssds", $email, $type, $purok, $price, $status); // s=string, s=string, s=string, d=decimal, s=string
        $success = $stmt->execute();
        echo json_encode(['message' => $success ? "Request submitted successfully" : "Failed to submit request"]);
    }
    exit;
}


if ($action === "adminGetIndigencyRequests") {
    $result = $conn->query("
        SELECT cr.*, r.name, r.lastname
        FROM certificate_requests cr
        LEFT JOIN registrations r ON cr.username = r.email
        WHERE cr.type='indigency'
        ORDER BY cr.date DESC
    ");
    $data = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($data);
    exit;
}


if ($action === "adminUpdateRequest2") {
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $msg = $_POST['adminMessage'] ?? '';

    if (!$id || !$status) {
        echo json_encode(["message"=>"Missing fields"]);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE certificate_requests 
        SET status=?, adminMessage=? 
        WHERE id=?
    ");
    $stmt->bind_param("ssi", $status, $msg, $id);
    echo json_encode(["message"=>$stmt->execute() ? "Updated" : "Failed"]);
    exit;
}
if ($action === "adminMarkPaid2") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { 
        echo json_encode(["message"=>"Missing ID"]); 
        exit; 
    }

    $stmt = $conn->prepare("UPDATE certificate_requests SET paid=1 WHERE id=?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();

    echo json_encode(["message" => $success ? "Marked as paid" : "Failed to mark as paid"]);
    exit;
}


if ($action === "adminDeleteRequest2") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(["message"=>"Missing ID"]); exit; }

    $conn->query("DELETE FROM certificate_requests WHERE id=$id");
    echo json_encode(["message"=>"Deleted"]);
    exit;
}



// ----------------------------
// Business Clearance 
// ----------------------------
if ($action === "adminGetBusinessRequests") {
    $result = $conn->query("
        SELECT cr.*, r.name, r.lastname
        FROM certificate_requests cr
        LEFT JOIN registrations r ON cr.username = r.email
        WHERE cr.type='business'
        ORDER BY cr.date DESC
    ");
    $data = $result->fetch_all(MYSQLI_ASSOC);
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

    // Validate required fields
    if (!$email || !$purpose || !$businessType || !$businessName || !$businessAddress || $price === null || $price < 0) {
        echo json_encode(['message' => 'All fields are required and price must be valid']);
        exit;
    }

    if ($id) {
        // UPDATE existing request
        $stmt = $conn->prepare("
            UPDATE certificate_requests
            SET purpose=?, businessType=?, businessName=?, businessAddress=?, price=?, type=?
            WHERE id=?
        ");
        $stmt->bind_param("ssssdsi", $purpose, $businessType, $businessName, $businessAddress, $price, $type, $id);
        $success = $stmt->execute();
        echo json_encode([
            'message' => $success ? 'Business request updated successfully' : 'Failed to update request'
        ]);
    } else {
        // INSERT new request
        $stmt = $conn->prepare("
            INSERT INTO certificate_requests 
            (username, type, purpose, businessType, businessName, businessAddress, price, status, date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
        ");
        $stmt->bind_param("ssssssd", $email, $type, $purpose, $businessType, $businessName, $businessAddress, $price);
        $success = $stmt->execute();
        echo json_encode([
            'message' => $success ? 'Business request submitted successfully' : 'Failed to submit request'
        ]);
    }
    exit;
}


if ($action === "getBusinessRequests") {
    $email = $_POST['email'] ?? '';
    if (!$email) { 
        echo json_encode([]); 
        exit; 
    }

    $stmt = $conn->prepare("
        SELECT id, username, type, purpose, businessType, businessName, businessAddress,
               price, status, adminMessage, paid, date
        FROM certificate_requests
        WHERE username=? AND type='business'
        ORDER BY date DESC
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    exit;
}



if ($action === "deleteBusinessRequest") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['message'=>'Missing ID']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM certificate_requests WHERE id=?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    echo json_encode([
        'message' => $success ? 'Request deleted successfully' : 'Failed to delete request'
    ]);
    exit;
}


if ($action === "adminUpdateBusinessRequest") {
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $msg = $_POST['adminMessage'] ?? '';

    if (!$id || !$status) {
        echo json_encode(["message"=>"Missing fields"]);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE certificate_requests 
        SET status=?, adminMessage=? 
        WHERE id=?
    ");
    $stmt->bind_param("ssi", $status, $msg, $id);
    echo json_encode(["message"=>$stmt->execute() ? "Updated" : "Failed"]);
    exit;
}

if ($action === "adminMarkBusinessPaid") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(["message"=>"Missing ID"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE certificate_requests SET paid=1 WHERE id=?");
    $stmt->bind_param("i", $id);
    echo json_encode([
        "message" => $stmt->execute() ? "Marked as paid" : "Failed"
    ]);
    exit;
}




// ================================
// ANNOUNCEMENTS SYSTEM
// ================================

// GET ALL RESIDENTS (ADMIN SIDE)
if ($action === "getResidents") {
    $result = $conn->query("
        SELECT email, name, lastname 
        FROM registrations
        ORDER BY name ASC
    ");

    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
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

    $stmt = $conn->prepare("
        INSERT INTO announcements (sender, recipient, message, date_sent)
        VALUES (?, ?, ?, NOW())
    ");

    foreach ($recipients as $recipient) {
        $stmt->bind_param("sss", $sender, $recipient, $message);
        $stmt->execute();
    }

    echo json_encode(["message" => "Announcement sent successfully"]);
    exit;
}



// GET ANNOUNCEMENTS (ADMIN & USER)
if ($action === "getAnnouncements") {
    $result = $conn->query("
        SELECT id, sender, recipient, message, date_sent
        FROM announcements
        ORDER BY date_sent DESC
    ");

    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    exit;
}


// DELETE MULTIPLE ANNOUNCEMENTS (ADMIN)
if ($action === "deleteAnnouncements") {
    $ids = json_decode($_POST['ids'] ?? '[]', true);

    if (!is_array($ids) || empty($ids)) {
        echo json_encode(["message" => "No announcements selected"]);
        exit;
    }

    // prepare placeholders (?, ?, ?)
    $placeholders = implode(",", array_fill(0, count($ids), "?"));
    $types = str_repeat("i", count($ids));

    $stmt = $conn->prepare(
        "DELETE FROM announcements WHERE id IN ($placeholders)"
    );
    $stmt->bind_param($types, ...$ids);

    $success = $stmt->execute();

    echo json_encode([
        "message" => $success
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

// Fetch announcements either sent to this user OR sent to everyone (sender marked special)
$stmt = $conn->prepare("
    SELECT id, sender, recipient, message, date_sent
    FROM announcements
    WHERE recipient = ? OR recipient = 'all_users'
    ORDER BY date_sent DESC
");

// If you want “all users” announcements to work, you must insert them with recipient = 'all_users'
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$announcements = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($announcements);
exit;

}


///////////////////////////////////////////

// Read raw input (JSON)
$input = json_decode(file_get_contents('php://input'), true);

// Determine action
$action = $_GET['action'] ?? ($input['action'] ?? '');

// ---------------- GET FEES ----------------
if($action === 'getCertificateFees') {
    $sql = "SELECT * FROM certificate_fees LIMIT 1";
    $result = $conn->query($sql);

    if($result && $row = $result->fetch_assoc()) {
        echo json_encode([
            'clearance' => $row['clearance'],
            'residency' => $row['residency'],
            'indigency' => $row['indigency'],
            'business' => $row['business']
        ]);
    } else {
        echo json_encode([]);
    }
    exit;
}

// ---------------- UPDATE FEES ----------------
if($action === 'updateCertificateFees') {
    $fees = $input['fees'] ?? null;

    if($fees) {
        $clearance = intval($fees['clearance']);
        $residency = intval($fees['residency']);
        $indigency = intval($fees['indigency']);
        $business = intval($fees['business']);

        // Update table (assuming single row)
        $sql = "UPDATE certificate_fees SET 
                    clearance = $clearance,
                    residency = $residency,
                    indigency = $indigency,
                    business = $business
                LIMIT 1";

        if($conn->query($sql)) {
            echo json_encode(['status'=>'success','message'=>'Fees updated successfully']);
        } else {
            echo json_encode(['status'=>'error','message'=>'Failed to update fees: '.$conn->error]);
        }
    } else {
        echo json_encode(['status'=>'error','message'=>'No fees data received']);
    }
    exit;
}





?>






