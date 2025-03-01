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

if (!isset($data['wallet_id']) || !isset($data['amount'])) {
    echo json_encode(["status" => "error", "message" => "Missing wallet ID or amount"]);
    exit();
}

$wallet_id = $data['wallet_id'];
$amount = floatval($data['amount']); // Ensure amount is a float

if ($amount <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid deposit amount"]);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Update wallet balance
    $updateWallet = $conn->prepare("UPDATE wallet SET balance = balance + ? WHERE wallet_id = ?");
    $updateWallet->bind_param("di", $amount, $wallet_id);
    $updateWallet->execute();

    // Fetch updated balance
    $result = $conn->prepare("SELECT balance FROM wallet WHERE wallet_id = ?");
    $result->bind_param("i", $wallet_id);
    $result->execute();
    $updatedBalance = $result->get_result()->fetch_assoc()['balance'];

    // Log transaction in the transactions table
    $insertTransaction = $conn->prepare("INSERT INTO transactions (transaction_type, status, amount, timestamp, wallet_id) VALUES ('Deposit', 'Completed', ?, NOW(), ?)");
    $insertTransaction->bind_param("di", $amount, $wallet_id);
    $insertTransaction->execute();

    // Commit transaction
    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => "✅ Deposit successful! Your new balance is $updatedBalance.",
        "updated_balance" => $updatedBalance
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => "❌ Deposit failed"]);
}

$conn->close();
?>
