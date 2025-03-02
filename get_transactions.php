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

// Fetch transactions along with currency type
$query = $conn->prepare("
    SELECT t.transaction_id, t.transaction_type, t.status, t.amount, t.timestamp, c.currency_type 
    FROM transactions t
    JOIN wallet w ON t.wallet_id = w.wallet_id
    LEFT JOIN currency c ON t.wallet_id = c.wallet_id  -- FIX: Left join to ensure all transactions appear
    WHERE w.user_id = ?
    AND t.transaction_type IN ('Sent', 'Receive', 'Deposit')
");

$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

if (empty($transactions)) {
    echo json_encode(["status" => "error", "message" => "No transactions found"]);
} else {
    echo json_encode(["status" => "success", "transactions" => $transactions]);
}

$query->close();
$conn->close();
?>
