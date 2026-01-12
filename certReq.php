<?php
header('Content-Type: application/json');
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

    // FIX: get action from GET or POST
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    // ----------------------------
    // GET REQUESTS
    // ----------------------------
    if ($action === "getRequests") {
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
    // SAVE / UPDATE REQUEST
    // ----------------------------
    elseif ($action === "saveRequest") {
        $email   = $_POST['email']   ?? '';
        $purpose = $_POST['purpose'] ?? '';
        $purok   = $_POST['purok']   ?? '';
        $price   = isset($_POST['price']) ? floatval($_POST['price']) : null;
        $age     = isset($_POST['age'])   ? intval($_POST['age'])   : null;
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
            // UPDATE
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
            exit;
        } else {
            // INSERT
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
    }// ----------------------------
// Delete request
// ----------------------------
elseif ($action === "deleteRequest") {
    $id = intval($_POST['id'] ?? 0);
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
elseif ($action === "getBusinessRequests") {
    $email = $_POST['email'] ?? '';
    if (!$email) { 
        echo json_encode([]); 
        exit; 
    }

    // PostgreSQL safe parameterized query
    $result = pg_query_params(
        $conn,
        "SELECT id, username, type, purpose, businesstype, businessname, businessaddress,
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
} // ----------------------------
// Submit / Update Business Request
// ----------------------------
elseif ($action === "saveBusinessRequest") {
    $email = $_POST['email'] ?? '';
    $purpose = $_POST['purpose'] ?? '';
    $businesstype = $_POST['businesstype'] ?? '';
    $businessname = $_POST['businessname'] ?? '';
    $businessaddress = $_POST['businessaddress'] ?? '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : null;
    $type = 'business';
    $id = $_POST['id'] ?? null;

    // Validation
    if (!$email || !$purpose || !$businesstype || !$businessname || !$businessaddress || $price === null || $price < 0) {
        echo json_encode(['message' => 'All fields are required and price must be valid.']);
        exit;
    }

    if ($id) {
        // UPDATE existing request
        $result = pg_query_params(
            $conn,
            "UPDATE certificate_requests
             SET purpose=$1, businesstype=$2, businessname=$3, businessaddress=$4, price=$5, type=$6
             WHERE id=$7",
            [$purpose, $businesstype, $businessname, $businessaddress, $price, $type, $id]
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
             (username, type, purpose, businesstype, businessname, businessaddress, price, status, date) 
             VALUES ($1, $2, $3, $4, $5, $6, $7, 'Pending', NOW())",
            [$email, $type, $purpose, $businesstype, $businessname, $businessaddress, $price]
        );

        echo json_encode([
            'message' => $result ? 'Business request submitted successfully' : 'Failed to submit request'
        ]);
        exit;
    }
}
// ----------------------------
// Get residency requests
// ----------------------------
elseif ($action === "getRequests1") {
    $email = $_POST['email'] ?? '';
    if (!$email) { 
        echo json_encode([]); 
        exit; 
    }

    // PostgreSQL safe parameterized query
    $result = pg_query_params(
        $conn,
        "SELECT id, username, type, purpose, purok, age, bioname, status, price, adminmessage, paid, date
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

    // ----------------------------
    // INVALID ACTION
    // ----------------------------
    else {
        echo json_encode(['message' => 'Invalid action']);
        exit;
    }
    
} catch (Exception $e) {
    echo json_encode(['message' => 'Error: ' . $e->getMessage()]);
    exit;
}
