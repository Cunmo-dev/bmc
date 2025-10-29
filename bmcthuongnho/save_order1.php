<?php
// save_order.php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/mongodb_handler.php';

// Lấy dữ liệu từ AJAX
$fullName = $_POST['fullName'] ?? '';
$phoneNumber = $_POST['phoneNumber'] ?? '';
$deliveryMethod = $_POST['deliveryMethod'] ?? '';
$noteForTelegram = $_POST['noteForTelegram'] ?? '';
$deliveryDetails = $_POST['deliveryDetails'] ?? '';
$orderItems = $_POST['orderItems'] ?? '';
$totalOrderValue = $_POST['totalOrderValue'] ?? 0;
$discountText = $_POST['discountText'] ?? '';
$shippingFee = $_POST['shippingFee'] ?? 0;
$finalTotal = $_POST['finalTotal'] ?? 0;
$storeName = $_POST['storeName'] ?? '';

// Gọi hàm createDataOrder
$dataOrder = createDataOrder(
    $fullName,
    $phoneNumber,
    $deliveryMethod,
    $noteForTelegram,
    $deliveryDetails,
    $orderItems,
    $totalOrderValue,
    $discountText,
    $shippingFee,
    $finalTotal,
    $storeName
);

function createDataOrder(
    $fullName,
    $phoneNumber,
    $deliveryMethod,
    $noteForTelegram,
    $deliveryDetails,
    $orderItems,
    $totalOrderValue,
    $discountText,
    $shippingFee,
    $finalTotal,
    $storeName
) {
    // Hàm format tiền
    $formatMoney = function ($value) {
        return number_format($value, 0, ',', '.') . '₫';
    };

    // Tạo mảng $dataOrder
    $dataOrder = [
        [
            'Name' => $fullName,
            'Telephone' => $phoneNumber,
            'deliveryMethod' => trim($deliveryMethod . ' ' . $noteForTelegram),
            'address' => $deliveryDetails,
            'totalOrderValue' => $formatMoney($totalOrderValue) . ($discountText ?? ''),
            'StoreInfo' => $storeName,
            'orderItem' => $orderItems,
            'shippingFee' => $formatMoney($shippingFee),
            'finalTotal' => $formatMoney($finalTotal),
            'created_at' => new \MongoDB\BSON\UTCDateTime() // Thêm thời gian tạo
        ]
    ];

    return $dataOrder;
}

// Khởi tạo kết nối MongoDB
$mongo = new MongoDBHandler('mongodb+srv://cunmoPro:Thanhcong140421%40@cluster0.s2sz5zy.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0', 'Allin');

// Thêm dữ liệu đơn hàng
foreach ($dataOrder as $order) {
    $result = $mongo->insertOne('orders', $order);
    if ($result) {
        echo "Đã thêm đơn hàng cho: " . $order['Name'] . " với ID: " . $result . "\n";
    } else {
        echo "Lỗi khi thêm đơn hàng cho: " . $order['Name'] . "\n";
    }
}

echo "Hoàn tất nhập dữ liệu đơn hàng!";
?>