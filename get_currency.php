<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

// Check if wallet_id is received
if (!isset($data['wallet_id'])) {
    echo json_encode(["status" => "error", "message" => "Missing wallet ID"]);
    exit();
}

$wallet_id = $data['wallet_id'];

// Debugging: Log received wallet_id
error_log("Received wallet_id: " . $wallet_id);

$query = $conn->prepare("SELECT currency_type, symbol, current_value FROM currency WHERE wallet_id = ?");
if (!$query) {
    echo json_encode(["status" => "error", "message" => "SQL Error: " . $conn->error]);
    exit();
}

$query->bind_param("i", $wallet_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    $currency = $result->fetch_assoc();
    echo json_encode(["status" => "success", "currency" => $currency]);
} else {
    echo json_encode(["status" => "error", "message" => "Currency not found"]);
}

$query->close();
$conn->close();
?>
