<?php
require "db.php";

$data = json_decode(file_get_contents("php://input"), true);

// email check
$check = $conn->prepare("SELECT id FROM registrations WHERE email=?");
$check->bind_param("s", $data["email"]);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
  http_response_code(400);
  echo json_encode(["message"=>"Email already exists"]);
  exit;
}

$hashed = password_hash($data["password"], PASSWORD_DEFAULT);

$stmt = $conn->prepare(
  "INSERT INTO registrations
  (name,email,password,phone,age,sex,birthday,address,validId,accountStatus)
  VALUES (?,?,?,?,?,?,?,?,?,'pending')"
);

$stmt->bind_param(
  "sssisssss",
  $data["name"],
  $data["email"],
  $hashed,
  $data["phone"],
  $data["age"],
  $data["sex"],
  $data["birthday"],
  $data["address"],
  $data["validId"]
);

$stmt->execute();

echo json_encode(["message"=>"Registration submitted"]);

