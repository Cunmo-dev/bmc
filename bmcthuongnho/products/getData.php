<?php
// Đặt tiêu đề HTTP để trả về JSON
header('Content-Type: application/json');

// Lấy tham số từ URL (ví dụ: ?file=menu-data)
$fileName = isset($_GET['file']) ? $_GET['file'] : null;

// Danh sách các file JSON được phép truy cập
$allowedFiles = [
    'anvattoping' => 'anvattoping.json',   // File JSON 1
    'banhtrang' => 'banhtrang.json',    // File JSON 2
    'bmc' => 'bmc.json', 
    'combo' => 'combo.json',   // File JSON 1
    'dochien' => 'dochien.json',    // File JSON 2
    'douong' => 'douong.json',
     'menu-data' => 'menu-data.json',   // File JSON 1
    'productData' => 'productData.json',
    'menu' => 'menu.json', 
];

// Kiểm tra xem file có hợp lệ không
if ($fileName && isset($allowedFiles[$fileName])) {
    $filePath = $allowedFiles[$fileName];
    
    // Kiểm tra xem file có tồn tại không
    if (file_exists($filePath)) {
        // Đọc nội dung file JSON
        $jsonData = file_get_contents($filePath);
        
        // Trả về nội dung JSON
        echo $jsonData;
    } else {
        // File không tồn tại
        http_response_code(404);
        echo json_encode(['error' => 'File không tồn tại']);
    }
} else {
    // Tham số không hợp lệ
    http_response_code(400);
    echo json_encode(['error' => 'Tham số không hợp lệ hoặc file không được phép truy cập']);
}
?>
