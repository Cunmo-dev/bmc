<?php
// File: send_telegram.php 
// Đặt file này ở phía server

// Cấu hình Telegram (lưu ở phía server)
const BOT_TOKEN = "7728268550:AAF-7rUSnQQNtrn3umKelpotyvYLVuIp5L8";
const CHAT_ID = "-4662422012";

// Nhận dữ liệu từ yêu cầu AJAX
$inputData = json_decode(file_get_contents('php://input'), true);

// Kiểm tra xác thực (có thể thêm nhiều biện pháp bảo mật hơn)
// Ví dụ: kiểm tra token, session, IP...
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Kiểm tra thêm CSRF token nếu cần
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

// Kiểm tra và làm sạch dữ liệu đầu vào
if (!isset($inputData['message']) || empty($inputData['message'])) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'error' => 'Message required']));
}

$message = htmlspecialchars($inputData['message'], ENT_QUOTES, 'UTF-8');

// Gửi tin nhắn đến Telegram
function sendTelegramMessage($message) {
    $url = 'https://api.telegram.org/bot' . BOT_TOKEN . '/sendMessage';
    
    $data = [
        'chat_id' => CHAT_ID,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return ['success' => false, 'error' => 'Failed to send message'];
    }
    
    return ['success' => true, 'data' => json_decode($response, true)];
}

// Ghi log (tùy chọn)
$logMessage = 'Telegram message sent: ' . $message . ' at ' . date('Y-m-d H:i:s');
error_log($logMessage, 3, 'telegram_logs.log');

// Gửi tin nhắn và trả về kết quả
$result = sendTelegramMessage($message);
header('Content-Type: application/json');
echo json_encode($result);
?>