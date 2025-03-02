<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['wallet_id'], $data['recipient_user_id'], $data['amount'])) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit();
}

$wallet_id = $data['wallet_id'];
$recipient_user_id = $data['recipient_user_id'];
$amount = floatval($data['amount']);

// Start transaction
$conn->begin_transaction();

try {
    // Get sender wallet details
    $senderQuery = $conn->prepare("SELECT user_id, balance FROM wallet WHERE wallet_id = ?");
    $senderQuery->bind_param("i", $wallet_id);
    $senderQuery->execute();
    $senderResult = $senderQuery->get_result();

    if ($senderResult->num_rows === 0) {
        throw new Exception("Sender wallet not found");
    }

    $sender = $senderResult->fetch_assoc();
    $sender_user_id = $sender['user_id'];
    $sender_balance = floatval($sender['balance']);

    if ($amount <= 0 || $amount > $sender_balance) {
        throw new Exception("Invalid amount or insufficient balance");
    }

    // Get recipient wallet
    $recipientQuery = $conn->prepare("SELECT wallet_id FROM wallet WHERE user_id = ?");
    $recipientQuery->bind_param("i", $recipient_user_id);
    $recipientQuery->execute();
    $recipientResult = $recipientQuery->get_result();

    if ($recipientResult->num_rows === 0) {
        throw new Exception("Recipient wallet not found");
    }

    $recipient = $recipientResult->fetch_assoc();
    $recipient_wallet_id = $recipient['wallet_id'];

    // Deduct from sender
    $updateSender = $conn->prepare("UPDATE wallet SET balance = balance - ? WHERE wallet_id = ?");
    $updateSender->bind_param("di", $amount, $wallet_id);
    $updateSender->execute();

    // Add to recipient
    $updateRecipient = $conn->prepare("UPDATE wallet SET balance = balance + ? WHERE wallet_id = ?");
    $updateRecipient->bind_param("di", $amount, $recipient_wallet_id);
    $updateRecipient->execute();

// Log transaction for sender
$logSenderTransaction = $conn->prepare("INSERT INTO transactions (transaction_type, status, amount, timestamp, wallet_id) VALUES ('Sent', 'Completed', ?, NOW(), ?)");
$logSenderTransaction->bind_param("di", $amount, $wallet_id);
$logSenderTransaction->execute();

// Log transaction for recipient
$logRecipientTransaction = $conn->prepare("INSERT INTO transactions (transaction_type, status, amount, timestamp, wallet_id) VALUES ('Recieve', 'Completed', ?, NOW(), ?)");
$logRecipientTransaction->bind_param("di", $amount, $recipient_wallet_id);
$logRecipientTransaction->execute();


    // Commit transaction
    $conn->commit();

    echo json_encode(["status" => "success", "message" => "✅ Crypto sent successfully"]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => "❌ " . $e->getMessage()]);
}

$senderQuery->close();
$recipientQuery->close();
$updateSender->close();
$updateRecipient->close();
$logSenderTransaction->close();
$logRecipientTransaction->close();
$conn->close();
?>
