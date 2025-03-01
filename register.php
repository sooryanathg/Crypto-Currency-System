<?php
include 'db.php'; // Ensure database connection is included

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$json = file_get_contents("php://input");
$data = json_decode($json, true);

// Debugging: Check if JSON is received
if (!$data) {
    echo json_encode(["status" => "error", "message" => "No JSON received"]);
    exit();
}

// Check if required fields exist
if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
    echo json_encode(["status" => "error", "message" => "Missing fields"]);
    exit();
}

$username = $data['username'];
$email = $data['email'];
$password = $data['password'];

// Check if email exists
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die(json_encode(["status" => "error", "message" => "SQL Error: " . $conn->error]));
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Email already exists"]);
    exit();
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$sql = "INSERT INTO users (username, email, password, balance) VALUES (?, ?, ?, 0)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die(json_encode(["status" => "error", "message" => "SQL Error: " . $conn->error]));
}

$stmt->bind_param("sss", $username, $email, $hashedPassword);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "User registered successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Error inserting user: " . $stmt->error]);
}

$conn->close();
?>
