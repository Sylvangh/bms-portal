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

        $result = pg_query($conn, "SELECT * FROM registrations WHERE accountstatus='approved'");
        if (!$result) {
            throw new Exception(pg_last_error($conn));
        }

        $residents = [];
        while ($row = pg_fetch_assoc($result)) {
            $residents[] = $row;
        }

        $response = $residents;
    }
/* ---------------- ADMIN SAVE RESIDENT ---------------- */
elseif ($action === "adminSaveResident") {
    try {
        ini_set('display_errors', 0);
        error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

        $id = intval($_POST['id'] ?? 0); // if ID exists = edit, else add

        // --- Handle file upload ---
        $validIdPath = null;
        if (isset($_FILES['validId']) && $_FILES['validId']['error'] === 0) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $filename = uniqid('id_') . '_' . basename($_FILES['validId']['name']);
            $validIdPath = $uploadDir . $filename;

            if (!move_uploaded_file($_FILES['validId']['tmp_name'], $validIdPath)) {
                throw new Exception("Failed to move uploaded file");
            }
        }

        // --- Collect all fields (match DB columns exactly) ---
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
            "pwd" => (($_POST['pwd'] ?? 'No') === 'Yes') ? TRUE : FALSE,
            "fourps" => (($_POST['fourPs'] ?? 'No') === 'Yes') ? TRUE : FALSE,
            "seniorcitizen" => (($_POST['seniorCitizen'] ?? '0') === '1') ? TRUE : FALSE,
            "schoollevels" => !empty($_POST['schoolLevels']) ? implode(",", $_POST['schoolLevels']) : '',
            "schoolname" => $_POST['schoolName'] ?? '',
            "occupation" => $_POST['occupation'] ?? '',
            "vaccinated" => (($_POST['vaccinated'] ?? '0') === '1') ? TRUE : FALSE,
            "voter" => (($_POST['voter'] ?? '0') === '1') ? TRUE : FALSE,
            "blottertheft" => (($_POST['blotter1'] ?? 'No') === 'Yes') ? 'Yes' : 'No',
            "blotterdisturbance" => (($_POST['blotter2'] ?? 'No') === 'Yes') ? 'Yes' : 'No',
            "blotterother" => (($_POST['blotter3'] ?? 'No') === 'Yes') ? 'Yes' : 'No',
        ];

        if ($validIdPath) $fields['validid'] = $validIdPath;

        // --- ADD RESIDENT ---
        if (!$id) {
            $columns = [];
            $placeholders = [];
            $values = [];
            $i = 1;

            foreach ($fields as $k => $v) {
                if ($v !== null) {
                    $columns[] = $k;
                    $placeholders[] = '$' . $i;
                    $values[] = $v;
                    $i++;
                }
            }

            $sql = "INSERT INTO registrations (" . implode(",", $columns) . ") VALUES (" . implode(",", $placeholders) . ")";
            $result = pg_query_params($conn, $sql, $values);

            if (!$result) {
                throw new Exception("Failed to add resident: " . pg_last_error($conn));
            }

            $response = ["status" => "success", "message" => "Resident added successfully"];
            pg_close($conn);
            exit;
        }

        // --- EDIT RESIDENT ---
        $set = [];
        foreach ($fields as $k => $v) {
            if ($v !== null) {
                // boolean TRUE/FALSE should not be quoted
                if (in_array($k, ['pwd','fourps','seniorcitizen','vaccinated','voter'])) {
                    $set[] = "$k=" . ($v ? 'TRUE' : 'FALSE');
                } else {
                    $set[] = "$k='" . pg_escape_string($v) . "'";
                }
            }
        }

        $sql = "UPDATE registrations SET " . implode(",", $set) . " WHERE id=$id";
        $result = pg_query($conn, $sql);

        if (!$result) {
            throw new Exception("Failed to update resident: " . pg_last_error($conn));
        }

        $response = ["status" => "success", "message" => "Resident updated successfully"];
        pg_close($conn);
        exit;

    } catch (Exception $e) {
        $response = ["status" => "error", "message" => $e->getMessage()];
        echo json_encode($response);
        pg_close($conn);
        exit;
    }
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

