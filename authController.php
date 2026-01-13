<?php
header('Content-Type: application/json');
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

$response = [];

try {
    // --- PostgreSQL connection ---
    $host = "dpg-d5g6o614tr6s73e42630-a.oregon-postgres.render.com";
    $db   = "bms_pen_db";
    $user = "bms_pen_db_user";
    $pass = "PuV1lCJedCOHqq2ZRJ2DYPCPWuWC5Ux6";
    $port = 5432;

    $conn_string = "host=$host port=$port dbname=$db user=$user password=$pass sslmode=require";
    $conn = @pg_connect($conn_string);
    if (!$conn) throw new Exception("Connection failed: " . pg_last_error());

    $action = $_GET['action'] ?? '';

    if ($action === 'register') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) throw new Exception("No input received");

        // --- sanitize and insert registration (as you already have) ---
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
        $schoollevels = !empty($data['schoollevels']) ? pg_escape_string(implode(",", $data['schoollevels'])) : '';
        $schoolname = pg_escape_string($data['schoolname'] ?? '');
        $occupation = pg_escape_string($data['occupation'] ?? '');
        $vaccinated = !empty($data['vaccinated']) ? 'TRUE' : 'FALSE';
        $voter = !empty($data['voter']) ? 'TRUE' : 'FALSE';
        $validIdBase64 = $data['validid'] ?? '';

        // Save Base64 ID
        $validid = null;
        if (!empty($validIdBase64)) {
            $validIdData = explode(',', $validIdBase64);
            $decoded = base64_decode($validIdData[1] ?? '');
            if ($decoded) {
                if (!is_dir('uploads')) mkdir('uploads', 0755, true);
                $filename = uniqid('id_') . '.png';
                $validid = 'uploads/' . $filename;
                @file_put_contents($validid, $decoded);
            }
        }

        // Check duplicate email
        $checkQuery = "SELECT 1 FROM registrations WHERE email='$email'";
        $check = pg_query($conn, $checkQuery);
        if (!$check) throw new Exception("Failed to query database: " . pg_last_error($conn));
        if (pg_num_rows($check) > 0) {
            $response = ["status" => "error", "message" => "Email already exists"];
        } else {
            $sql = "INSERT INTO registrations 
                (name, middlename, lastname, email, password, accountstatus, phone, age, sex, birthday, address, status, pwd, fourps, seniorcitizen, schoollevels, schoolname, occupation, vaccinated, voter, validid)
                VALUES
                ('$name', '$middlename', '$lastname', '$email', '$password', 'pending', '$phone', $age, '$sex', '$birthday', '$address', '$status', '$pwd', '$fourps', $seniorcitizen, '$schoollevels', '$schoolname', '$occupation', $vaccinated, $voter, '$validid')";

            $result = pg_query($conn, $sql);
            if (!$result) throw new Exception("Failed to insert registration: " . pg_last_error($conn));
            $response = ["status" => "success", "message" => "Registration request submitted"];
        }
} elseif ($action === 'adminLogin') {
        // --- Admin login ---
        $ADMIN_USERNAME = "admin";
        $ADMIN_PASSWORD = "#KapTata2026";

        $input = json_decode(file_get_contents("php://input"), true);
        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');

        if (!$username || !$password) throw new Exception("Username and password required");

        if ($username === $ADMIN_USERNAME && $password === $ADMIN_PASSWORD) {
            $_SESSION['admin_logged_in'] = true;
            $response = ["status" => "success", "message" => "Login successful"];
        } else {
            $response = ["status" => "error", "message" => "Invalid username or password"];
        }

    } /* ---------------- GET ALL RESIDENTS ---------------- */
    elseif ($action === "getAllResidents") {

        $result = pg_query($conn, "SELECT * FROM registrations ORDER BY id DESC");
        if (!$result) {
            throw new Exception(pg_last_error($conn));
        }

        $residents = [];
        while ($row = pg_fetch_assoc($result)) {
            if (isset($row['accountstatus'])) {
                $row['accountstatus'] = strtolower($row['accountstatus']);
            }
            $residents[] = $row;
        }

        $response = $residents;
    }

    /* ---------------- UPDATE STATUS ---------------- */
    elseif ($action === "updateStatus") {

        $id = (int)($_GET['id'] ?? 0);
        $status = $_GET['status'] ?? '';

        if (!$id || !$status) {
            throw new Exception("Missing id or status");
        }

        $status = pg_escape_string($status);
        $sql = "UPDATE registrations SET accountstatus='$status' WHERE id=$id";

        if (!pg_query($conn, $sql)) {
            throw new Exception(pg_last_error($conn));
        }

        $response = [
            "status" => "success",
            "message" => "Resident status updated to $status"
        ];
    }

    /* ---------------- DELETE RESIDENT ---------------- */
    elseif ($action === "deleteResident") {

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            throw new Exception("Missing id");
        }

        if (!pg_query($conn, "DELETE FROM registrations WHERE id=$id")) {
            throw new Exception(pg_last_error($conn));
        }

        $response = [
            "status" => "success",
            "message" => "Resident deleted successfully"
        ];
    }
        
/* ---------------- ADMIN GET APPROVED RESIDENTS ---------------- */
elseif ($action === "adminGetResidents") {

    $sql = "
        SELECT 
            id,
            email,
            name,
            middlename,
            lastname,
            phone,
            age,
            sex,
            birthday,
            address,
            status,
            pwd,
            fourps,
            seniorcitizen::int,
            vaccinated::int,
            voter::int,
            schoollevels,
            schoolname,
            occupation,
            validid,
            blottertheft,
            blotterdisturbance,
            blotterother
        FROM registrations
        WHERE accountstatus = 'approved'
    ";

    $result = pg_query($conn, $sql);

    if (!$result) {
        echo json_encode(["status" => "error", "message" => pg_last_error($conn)]);
        exit;
    }

    $residents = [];
    while ($row = pg_fetch_assoc($result)) {
        $residents[] = $row;
    }

    echo json_encode($residents);
    exit;
}
        

elseif ($action === "adminSaveResident") {
    $id = intval($_POST['id'] ?? 0);

    // ---------------- FILE UPLOAD ----------------
    $validIdPath = null;

  if (!empty($_FILES['validid']) && $_FILES['validid']['error'] === 0) {
    $uploadDir = __DIR__ . "/uploads/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $filename = uniqid("id_") . "_" . basename($_FILES['validid']['name']);
    $filePath = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['validid']['tmp_name'], $filePath)) {
        $validIdPath = "uploads/" . $filename;
    }
}

    // ---------------- PREPARE FIELDS ----------------
    $fields = [
        "email" => $_POST['username'] ?? '',
        "password" => !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null,
        "name" => $_POST['fname'] ?? '',
        "middlename" => $_POST['mname'] ?? '',
        "lastname" => $_POST['lname'] ?? '',
        "phone" => $_POST['mPhone'] ?? '',
        "age" => intval($_POST['age'] ?? 0),
        "sex" => $_POST['sex'] ?? '',
        "birthday" => $_POST['birthday'] ?? null,
        "address" => $_POST['address'] ?? '',
        "status" => $_POST['status'] ?? '',
        "pwd" => ($_POST['pwd'] ?? 'No') === 'Yes' ? 'Yes' : 'No',
        "fourps" => ($_POST['fourps'] ?? 'No') === 'Yes' ? 'Yes' : 'No',

        // ✅ CHECKBOXES
        "seniorcitizen" => isset($_POST['seniorcitizen']) && $_POST['seniorcitizen'] === '1' ? 1 : 0,
        "vaccinated"   => isset($_POST['vaccinated']) && $_POST['vaccinated'] === '1' ? 1 : 0,
        "voter"        => isset($_POST['voter']) && $_POST['voter'] === '1' ? 1 : 0,

        // ---------------- SCHOOL ----------------
        "schoollevels" => !empty($_POST['schoollevels'])
            ? (is_array($_POST['schoollevels']) ? implode(',', $_POST['schoollevels']) : $_POST['schoollevels'])
            : '',
        "schoolname" => $_POST['schoolname'] ?? '',
        "occupation" => $_POST['occupation'] ?? '',

        // ---------------- BLOTTERS ----------------
        "blottertheft" => ($_POST['blotter1'] ?? 'No') === 'Yes' ? 'Yes' : 'No',
        "blotterdisturbance" => ($_POST['blotter2'] ?? 'No') === 'Yes' ? 'Yes' : 'No',
        "blotterother" => ($_POST['blotter3'] ?? 'No') === 'Yes' ? 'Yes' : 'No',
    ];


// Only save validid if uploaded
if ($validIdPath !== null) $fields['validid'] = $validIdPath;

    // ---------------- INSERT OR UPDATE ----------------
    if (!$id) {
        // INSERT
        $cols = [];
        $vals = [];
        $params = [];
        $i = 1;

        foreach ($fields as $k => $v) {
            $cols[] = $k;
            $vals[] = '$' . $i;
            $params[] = $v;
            $i++;
        }

        $sql = "INSERT INTO registrations (" . implode(",", $cols) . ") VALUES (" . implode(",", $vals) . ")";
        $res = pg_query_params($conn, $sql, $params);

        if (!$res) {
            echo json_encode(["status"=>"error","message"=>pg_last_error($conn)]);
            exit;
        }

        echo json_encode(["status"=>"success","message"=>"Resident added successfully"]);
        exit;

} else {
    $params = [];
    $set = [];
    $i = 1;

    foreach ($fields as $k => $v) {
        // skip password only if null
        if ($k === 'password' && $v === null) continue;

        $set[] = "$k = $" . $i;
        $params[] = $v;
        $i++;
    }

    $params[] = $id;
    $sql = "UPDATE registrations SET " . implode(",", $set) . " WHERE id = $" . $i;
    $res = pg_query_params($conn, $sql, $params);

    if (!$res) {
        echo json_encode(["status" => "error", "message" => pg_last_error($conn)]);
        exit;
    }

    echo json_encode(["status" => "success", "message" => "Resident updated successfully"]);
    exit;
}
}elseif ($action === "residentLogin") {
    // Get JSON input
    $data = json_decode(file_get_contents("php://input"), true);

    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    // ---------------- POSTGRESQL QUERY ----------------
    $sql = "SELECT * FROM registrations WHERE email = $1 AND accountstatus = 'approved' LIMIT 1";
    $res = pg_query_params($conn, $sql, [$email]);

    if (!$res || pg_num_rows($res) === 0) {
        http_response_code(401);
        echo json_encode(["message" => "Resident not found or not approved"]);
        exit;
    }

    $resident = pg_fetch_assoc($res);

    // ---------------- PASSWORD CHECK ----------------
    if (!password_verify($password, $resident['password'])) {
        http_response_code(401);
        echo json_encode(["message" => "Incorrect password"]);
        exit;
    }

    // ---------------- GENERATE TOKEN ----------------
    $token = bin2hex(random_bytes(16));

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
}/*
elseif ($action === "getResident") {

    $email = trim($_GET['email'] ?? '');
    if (!$email) {
        echo json_encode(["message" => "Missing email"]);
        exit;
    }


    $sql = "SELECT * FROM registrations WHERE email = $1 AND accountstatus = 'approved' LIMIT 1";
    $res = pg_query_params($conn, $sql, [$email]);

    if (!$res || pg_num_rows($res) === 0) {
        echo json_encode(["message" => "Resident not found or not approved"]);
        exit;
    }

    $resident = pg_fetch_assoc($res);
    echo json_encode($resident);
    exit;
}*/
       elseif ($action === "getResident") {
    // ---------------- GET RESIDENT ----------------
    $id = trim($_GET['id'] ?? '');
    if (!$id) {
        echo json_encode(["status" => "error", "message" => "Missing user ID"]);
        exit;
    }

    // PostgreSQL query by ID
    $sql = "SELECT * FROM registrations WHERE id = $1 AND accountstatus = 'approved' LIMIT 1";
    $res = pg_query_params($conn, $sql, [$id]);

    if (!$res || pg_num_rows($res) === 0) {
        echo json_encode(["status" => "error", "message" => "Resident not found or not approved"]);
        exit;
    }

    $resident = pg_fetch_assoc($res);

    // ✅ normalize keys to lowercase
    $resident = array_change_key_case($resident, CASE_LOWER);

    echo json_encode($resident);
    exit;
}elseif ($action === "updateResident") {
    // ---------------- UPDATE RESIDENT ----------------
    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(["status" => "error", "message" => "Missing user ID"]);
        exit;
    }

    // Handle file upload
    $validid = null;
    if (isset($_FILES['validid']) && $_FILES['validid']['error'] === 0) {
        $filename = uniqid('id_') . '_' . basename($_FILES['validid']['name']);
        $validid = 'uploads/' . $filename;
        if (!move_uploaded_file($_FILES['validid']['tmp_name'], $validid)) {
            echo json_encode(["status" => "error", "message" => "Failed to upload file"]);
            exit;
        }
    }

    // Prepare fields (convert all name/text fields to lowercase)
    $fields = [
        "email" => strtolower(trim($_POST['email'] ?? '')),
        "name" => strtolower(trim($_POST['name'] ?? '')),
        "middlename" => strtolower(trim($_POST['middlename'] ?? '')),
        "lastname" => strtolower(trim($_POST['lastname'] ?? '')),
        "phone" => trim($_POST['phone'] ?? ''),
        "age" => isset($_POST['age']) ? intval($_POST['age']) : 0,
        "sex" => trim($_POST['sex'] ?? ''),
        "birthday" => $_POST['birthday'] ?? '',
        "address" => trim($_POST['address'] ?? ''),
        "status" => trim($_POST['status'] ?? ''),
        "pwd" => (($_POST['pwd'] ?? 'No') === 'Yes') ? 'Yes' : 'No',
        "fourps" => (($_POST['fourps'] ?? 'No') === 'Yes') ? 'Yes' : 'No',
        "seniorcitizen" => (($_POST['seniorcitizen'] ?? 0) == 1) ? 'TRUE' : 'FALSE',
        "schoollevels" => strtolower(trim($_POST['schoollevels'] ?? '')),
        "schoolname" => strtolower(trim($_POST['schoolname'] ?? '')),
        "occupation" => strtolower(trim($_POST['occupation'] ?? '')),
        "vaccinated" => (($_POST['vaccinated'] ?? 0) == 1) ? 'TRUE' : 'FALSE',
        "voter" => (($_POST['voter'] ?? 0) == 1) ? 'TRUE' : 'FALSE',
        "blottertheft" => (($_POST['blottertheft'] ?? 'No') === 'Yes') ? 'Yes' : 'No',
        "blotterdisturbance" => (($_POST['blotterdisturbance'] ?? 'No') === 'Yes') ? 'Yes' : 'No',
        "blotterother" => (($_POST['blotterother'] ?? 'No') === 'Yes') ? 'Yes' : 'No'
    ];

    // Optional: hash password if provided
    if (!empty($_POST['password'])) {
        $fields['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
    }

    // Include uploaded file if exists
    if ($validid) {
        $fields['validid'] = $validid;
    }

    // Build dynamic UPDATE query
    $set = [];
    $params = [];
    $i = 1;
    foreach ($fields as $k => $v) {
        $set[] = "$k = $" . $i;
        $params[] = $v;
        $i++;
    }

    $params[] = $id; // for WHERE clause
    $sql = "UPDATE registrations SET " . implode(", ", $set) . " WHERE id = $" . $i;

    $result = pg_query_params($conn, $sql, $params);

    if ($result) {
        echo json_encode(["status" => "success", "message" => "User updated successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => pg_last_error($conn)]);
    }

    pg_close($conn);
    exit;
}
    elseif ($action === "getResidents") {
    $sql = "SELECT id, name, lastname, email 
            FROM registrations 
            WHERE accountstatus = 'approved'
            ORDER BY name ASC";

    $res = pg_query($conn, $sql);

    if (!$res) {
        echo json_encode([]);
        exit;
    }

    $rows = pg_fetch_all($res);
    echo json_encode($rows ?: []);
    exit;
}
    elseif ($action === "sendAnnouncement") {

    $sender = "Admin";
    $message = trim($_POST['message'] ?? '');
    $recipients = json_decode($_POST['recipients'] ?? '[]', true);

    if ($message === '' || !is_array($recipients) || empty($recipients)) {
        echo json_encode([
            "status" => "error",
            "message" => "Missing message or recipients"
        ]);
        exit;
    }

    foreach ($recipients as $recipient) {
        pg_query_params(
            $conn,
            "INSERT INTO announcements (sender, recipient, message, date_sent)
             VALUES ($1, $2, $3, NOW())",
            [$sender, $recipient, $message]
        );
    }

    echo json_encode([
        "status" => "success",
        "message" => "Announcement sent successfully"
    ]);
    exit;
}
        elseif ($action === "getAnnouncements") {

    // OPTIONAL: filter by recipient (for user side)
    $recipient = $_GET['recipient'] ?? null;

    if ($recipient) {
        // USER: only their announcements
        $sql = "
            SELECT id, sender, recipient, message, date_sent
            FROM announcements
            WHERE recipient = $1 OR recipient = 'ALL'
            ORDER BY date_sent DESC
        ";
        $result = pg_query_params($conn, $sql, [$recipient]);
    } else {
        // ADMIN: see all announcements
        $sql = "
            SELECT id, sender, recipient, message, date_sent
            FROM announcements
            ORDER BY date_sent DESC
        ";
        $result = pg_query($conn, $sql);
    }

    if (!$result) {
        echo json_encode([
            "status" => "error",
            "message" => pg_last_error($conn)
        ]);
        exit;
    }

    $announcements = [];
    while ($row = pg_fetch_assoc($result)) {
        $announcements[] = $row;
    }

    echo json_encode($announcements);
    exit;
}

elseif ($action === 'updateCertificateFees') {
    $input = json_decode(file_get_contents('php://input'), true);
    $fees = $input['fees'] ?? null;

    if (!$fees) {
        echo json_encode([
            "status" => "error",
            "message" => "No fees data received"
        ]);
        exit;
    }

    $clearance = intval($fees['clearance'] ?? 0);
    $residency = intval($fees['residency'] ?? 0);
    $indigency = intval($fees['indigency'] ?? 0);
    $business  = intval($fees['business'] ?? 0);

    // Ensure the row exists
    $rowCheck = pg_query($conn, "SELECT id FROM certificate_fees LIMIT 1");
    if (pg_num_rows($rowCheck) > 0) {
        // UPDATE existing row
        $sql = "UPDATE certificate_fees SET
                    clearance = $1,
                    residency = $2,
                    indigency = $3,
                    business = $4
                WHERE id = (SELECT id FROM certificate_fees LIMIT 1)";
    } else {
        // INSERT a new row if table empty (provide fee = 0)
        $sql = "INSERT INTO certificate_fees (fee, clearance, residency, indigency, business)
                VALUES (0, $1, $2, $3, $4)";
    }

    $result = pg_query_params($conn, $sql, [$clearance, $residency, $indigency, $business]);

    if ($result) {
        echo json_encode([
            "status" => "success",
            "message" => "Fees updated successfully"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => pg_last_error($conn)
        ]);
    }
    exit;
}
        elseif ($action === "getCertificateFees") {
    $result = pg_query($conn, "SELECT * FROM certificate_fees LIMIT 1");

    if (!$result || pg_num_rows($result) === 0) {
        echo json_encode([
            "clearance" => 0,
            "residency" => 0,
            "indigency" => 0,
            "business"  => 0
        ]);
        exit;
    }

    $fees = pg_fetch_assoc($result);
    $fees = array_change_key_case($fees, CASE_LOWER);

    echo json_encode($fees);
    exit;
}
elseif ($action === "getRequests") {
    $email = $_POST['email'] ?? '';
    if (!$email) { 
        echo json_encode([]); 
        exit; 
    }

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
// Admin: Get all clearance requests
// ----------------------------
elseif ($action === "adminGetClearanceRequests") {
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
// ----------------------------
// Admin: Update request status / message
// ----------------------------
elseif ($action === "adminUpdateRequest") {
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
// ----------------------------
// Admin: Mark request as paid
// ----------------------------
elseif ($action === "adminMarkPaid") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { 
        echo json_encode(["message" => "Missing ID"]); 
        exit; 
    }

    $result = pg_query_params(
        $conn,
        "UPDATE certificate_requests SET paid = TRUE WHERE id = $1",
        [$id]
    );

    echo json_encode([
        "message" => $result ? "Marked as paid" : "Failed to mark as paid"
    ]);
    exit;
}
   elseif ($action === "adminDeleteRequest") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { 
        echo json_encode(["message" => "Missing ID"]); 
        exit; 
    }

    $result = pg_query_params(
        $conn,
        "DELETE FROM certificate_requests WHERE id=$1",
        [$id]
    );

    echo json_encode(["message" => "Deleted"]);
    exit;
}
        // ----------------------------
// Admin: Get Business Requests
// ----------------------------
elseif ($action === "admingetbusinessrequests") {
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
// ----------------------------
// Admin: Update Business Request
// ----------------------------
elseif ($action === "adminUpdateBusinessRequest") {
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $msg = $_POST['adminmessage'] ?? ''; // match DB column lowercase

    if (!$id || !$status) {
        echo json_encode(["message" => "Missing fields"]);
        exit;
    }

    // PostgreSQL safe parameterized query
    $result = pg_query_params(
        $conn,
        "UPDATE certificate_requests 
         SET status=$1, adminmessage=$2 
         WHERE id=$3",
        [$status, $msg, $id]
    );

    echo json_encode([
        "message" => $result ? "Updated" : "Failed"
    ]);
    exit;
}
// ----------------------------
// Admin: Mark Business Request as Paid
// ----------------------------
elseif ($action === "adminMarkBusinessPaid") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(["message" => "Missing ID"]);
        exit;
    }

    // Set paid = true explicitly (PostgreSQL boolean)
    $result = pg_query_params(
        $conn,
        "UPDATE certificate_requests SET paid=true WHERE id=$1",
        [$id]
    );

    echo json_encode([
        "message" => $result ? "Marked as paid" : "Failed"
    ]);
    exit;
}

        // ----------------------------
// Admin: Delete Business Request
// ----------------------------
elseif ($action === "deleteBusinessRequest") {
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

        // ----------------------------
// Admin: Get Residency Requests
// ----------------------------
elseif ($action === "AdmingetResidencyRequests") {

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

// ----------------------------
// Admin: Update Residency Request
// ----------------------------
elseif ($action === "adminUpdateRequest1") { 
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $msg = $_POST['adminmessage'] ?? ''; 

    if (!$id || !$status) {
        echo json_encode(["message" => "Missing fields"]);
        exit;
    }

    // PostgreSQL safe parameterized query
    $result = pg_query_params(
        $conn,
        "UPDATE certificate_requests 
         SET status=$1, adminmessage=$2 
         WHERE id=$3",
        [$status, $msg, $id]
    );

    echo json_encode([
        "message" => $result ? "Updated" : "Failed"
    ]);
    exit;
}
// ----------------------------
// Admin: Mark Residency Request as Paid
// ----------------------------
    
elseif ($action === "adminMarkPaid1") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(["message" => "Missing ID"]);
        exit;
    }

    // Use RETURNING to ensure a row was updated
    $result = pg_query_params(
        $conn,
        "UPDATE certificate_requests SET paid=true WHERE id=$1 RETURNING id",
        [$id]
    );

    $row = pg_fetch_assoc($result);

    echo json_encode([
        "message" => $row ? "Marked as paid" : "Failed to mark as paid"
    ]);
    exit;
}


// ----------------------------
// Admin: Delete Residency Request
// ----------------------------
elseif ($action === "adminDeleteRequest1") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { 
        echo json_encode(["message" => "Missing ID"]); 
        exit; 
    }

    // PostgreSQL safe deletion
    $result = pg_query_params($conn, "DELETE FROM certificate_requests WHERE id=$1", [$id]);

    echo json_encode([
        "message" => $result ? "Deleted" : "Failed to delete request"
    ]);
    exit;
}
       elseif ($action === "adminGetIndigencyRequests") {
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
        
elseif ($action === "adminUpdateRequest2") {
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $msg = $_POST['adminmessage'] ?? '';

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

elseif ($action === "adminMarkPaid2") {
    $id = intval($_POST['id'] ?? 0);

    if (!$id) {
        echo json_encode(["message" => "Missing ID"]);
        exit;
    }

    $result = pg_query_params(
        $conn,
        "UPDATE certificate_requests 
         SET paid = TRUE 
         WHERE id = $1",
        [$id]
    );

    if ($result && pg_affected_rows($result) > 0) {
        echo json_encode(["message" => "Marked as paid"]);
    } else {
        echo json_encode(["message" => "Failed to mark as paid"]);
    }
    exit;
}

     elseif
($action === "adminDeleteRequest2") {
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
elseif ($action === "getAllResi") {

    $result = pg_query($conn, "SELECT * FROM registrations");
    $residents = [];

    while ($row = pg_fetch_assoc($result)) {
        $row['accountstatus'] = strtolower(trim($row['accountstatus']));
        $residents[] = $row;
    }

    echo json_encode($residents);
    exit;
}


        // ----------------------------
// Get pending clearance count
// ----------------------------
elseif ($action === "getPendingClearanceCount") {

    $result = pg_query(
        $conn,
        "SELECT COUNT(*) AS pendingcount 
         FROM certificate_requests 
         WHERE LOWER(status) = 'pending'"
    );

    if (!$result) {
        echo json_encode(["pendingClearance" => 0]);
        exit;
    }

    $row = pg_fetch_assoc($result);

    echo json_encode([
        "pendingClearance" => intval($row['pendingcount'])
    ]);
    exit;
}

        elseif ($action === "adminDeleteResident") {
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

        
elseif ($action === "deleteAnnouncements") {

    $ids = json_decode($_POST['ids'] ?? '[]', true);

    if (!is_array($ids) || empty($ids)) {
        echo json_encode(["message" => "No announcements selected"]);
        exit;
    }

    // Sanitize IDs (force integers)
    $ids = array_map('intval', $ids);

    // Build IN clause: 1,2,3
    $idList = implode(',', $ids);

    $sql = "DELETE FROM announcements WHERE id IN ($idList)";
    $result = pg_query($conn, $sql);

    echo json_encode([
        "message" => $result
            ? "Selected announcements deleted"
            : "Failed to delete announcements"
    ]);
    exit;
}



/* ---------------- INVALID ACTION ---------------- */
else {
    throw new Exception("Invalid action");
}

} catch (Exception $e) {
    $response = ["status" => "error", "message" => $e->getMessage()];
}

echo json_encode($response);
exit();



























































































