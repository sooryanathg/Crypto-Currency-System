<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User ID is required"]);
    exit();
}

$user_id = $data['user_id'];

// Fetch all wallets belonging to the user
$walletQuery = $conn->prepare("SELECT wallet_id FROM wallet WHERE user_id = ?");
$walletQuery->bind_param("i", $user_id);
$walletQuery->execute();
$walletResult = $walletQuery->get_result();

$wallet_ids = [];
while ($row = $walletResult->fetch_assoc()) {
    $wallet_ids[] = $row['wallet_id'];
}

if (empty($wallet_ids)) {
    echo json_encode(["status" => "error", "message" => "No wallets found for this user"]);
    exit();
}

// Fetch all transactions for the user's wallets
$wallet_ids_placeholder = implode(',', array_fill(0, count($wallet_ids), '?'));
$query = $conn->prepare("SELECT transaction_id, transaction_type, status, amount, timestamp FROM transactions WHERE wallet_id IN ($wallet_ids_placeholder)");

$query->bind_param(str_repeat('i', count($wallet_ids)), ...$wallet_ids);
$query->execute();
$result = $query->get_result();

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

echo json_encode(["status" => "success", "transactions" => $transactions]);

$query->close();
$conn->close();
?>
