<?php

// CORS Headers
header("Access-Control-Allow-Origin: *"); // Allow all origins (or specify 'http://localhost:3000' for security)
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // Allowed methods
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allowed headers
header("Content-Type: application/json");

// Handle Preflight Request
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

// Include Database Connection
include 'db.php'; 

if (!isset($conn)) {
    echo json_encode(["status" => "error", "message" => "Database connection error"]);
    exit();
}

// Read and decode JSON input
$json = file_get_contents("php://input");
$data = json_decode($json, true);

// Debugging: Check if JSON is received
if (!$data) {
    echo json_encode(["status" => "error", "message" => "No JSON received"]);
    exit();
}

// Check if required fields exist
if (empty($data['email']) || empty($data['password'])) {
    echo json_encode(["status" => "error", "message" => "Missing email or password"]);
    exit();
}

$email = $data['email'];
$password = $data['password'];

// Check if email exists in the database
$sql = "SELECT user_id, username, password FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "SQL Error: " . $conn->error]);
    exit();
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit();
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user['password'])) {
    echo json_encode(["status" => "error", "message" => "Invalid password"]);
    exit();
}

// ✅ Return user_id and username on successful login
echo json_encode([
    "status" => "success",
    "message" => "Login successful",
    "user_id" => $user['user_id'],
    "username" => $user['username']
]);

$conn->close();
?>
