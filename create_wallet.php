<?php
// Allow CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

// Allow requests from any origin (adjust as needed)
header("Access-Control-Allow-Origin: *");

// Allow specific HTTP methods
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// Allow specific headers
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id']) || !isset($data['currency_type']) || !isset($data['balance'])) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit();
}

$user_id = $data['user_id'];
$currency_type = $data['currency_type'];
$balance = $data['balance'];

// Check if user exists
$userCheck = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
$userCheck->bind_param("i", $user_id);
$userCheck->execute();
$userCheck->store_result();

if ($userCheck->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit();
}

// Insert new wallet
$stmt = $conn->prepare("INSERT INTO wallet (user_id, currency_type, balance) VALUES (?, ?, ?)");
$stmt->bind_param("isi", $user_id, $currency_type, $balance);

if ($stmt->execute()) {
    $wallet_id = $stmt->insert_id; // Get the newly created wallet ID

    // Set default values for currency
    $symbol = "";
    $current_value = 0.00;

    // Define symbols and current values for some known currencies
    $currency_data = [
        "Bitcoin" => ["symbol" => "₿", "value" => 50000],
        "Ethereum" => ["symbol" => "Ξ", "value" => 3000],
        "Litecoin" => ["symbol" => "Ł", "value" => 150]
    ];

    if (array_key_exists($currency_type, $currency_data)) {
        $symbol = $currency_data[$currency_type]["symbol"];
        $current_value = $currency_data[$currency_type]["value"];
    }

    // Insert currency details into the currency table
    $stmt2 = $conn->prepare("INSERT INTO currency (currency_type, symbol, current_value, wallet_id) VALUES (?, ?, ?, ?)");
    $stmt2->bind_param("ssdi", $currency_type, $symbol, $current_value, $wallet_id);

    if ($stmt2->execute()) {
        echo json_encode(["status" => "success", "message" => "Wallet and currency created successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to insert currency data"]);
    }

    $stmt2->close();
} else {
    echo json_encode(["status" => "error", "message" => "Failed to create wallet"]);
}

$stmt->close();
$conn->close();
?>
