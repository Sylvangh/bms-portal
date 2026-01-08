<?php
header('Content-Type: application/json');
include 'db.php';

$action = $_GET['action'] ?? '';

if ($action === "getPending") {
    $stmt = $pdo->query("SELECT * FROM registrations WHERE accountStatus='pending' ORDER BY createdAt DESC");
    $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($pending);
}

// ---------------- APPROVE ----------------
if ($action === "approve") {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE registrations SET accountStatus='approved' WHERE id=?");
    $success = $stmt->execute([$id]);
    echo json_encode(["message" => $success ? "Request approved" : "Failed to approve"]);
}

// ---------------- REJECT ----------------
if ($action === "reject") {
    $id = $_GET['id'];
    $data = json_decode(file_get_contents("php://input"), true);
    $message = $data['message'] ?? '';
    $stmt = $pdo->prepare("UPDATE registrations SET accountStatus='rejected', adminMessage=? WHERE id=?");
    $success = $stmt->execute([$message, $id]);
    echo json_encode(["message" => $success ? "Request rejected" : "Failed to reject"]);
}

// ---------------- REMOVE ----------------
if ($action === "remove") {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM registrations WHERE id=?");
    $success = $stmt->execute([$id]);
    echo json_encode(["message" => $success ? "Request removed" : "Failed to remove"]);
}
?>
