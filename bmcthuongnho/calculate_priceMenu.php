<?php
header('Content-Type: application/json');

// Kiểm tra request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Phương thức không được hỗ trợ']);
    exit;
}

// Lấy dữ liệu từ request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['sku']) || !isset($data['quantity']) || !isset($data['toppings'])) {
    echo json_encode(['error' => 'Thiếu thông tin cần thiết']);
    exit;
}

$sku = $data['sku'];
$quantity = intval($data['quantity']);
$toppings = $data['toppings']; // Mảng chứa các topping đã chọn và số lượng
$extraWaters = isset($data['extraWaters']) ? $data['extraWaters'] : []; // Mảng chứa các đồ uống kèm đã chọn và số lượng

// Đọc file JSON chứa thông tin sản phẩm
$productsJson = file_get_contents('products/menu.json'); // Thay đổi đường dẫn phù hợp
$products = json_decode($productsJson, true);

// Tìm sản phẩm theo SKU
$product = null;
foreach ($products as $key => $value) {
    if (isset($value['productSku']) && $value['productSku'] === $sku) {
        $product = $value;
        break;
    }
}

if (!$product) {
    echo json_encode(['error' => 'Không tìm thấy sản phẩm']);
    exit;
}

// Lấy giá sản phẩm
$basePrice = intval(str_replace(['₫', ',', '.'], '', $product['productPrice']));
$productTotal = $basePrice * $quantity;

// Tính tổng giá topping
$toppingTotal = 0;
if (!empty($product['toppings']) && !empty($toppings)) {
    foreach ($toppings as $toppingItem) {
        $toppingValue = $toppingItem['value'];
        $toppingQuantity = intval($toppingItem['quantity']);
        
        // Tìm topping trong danh sách
        foreach ($product['toppings'] as $availableTopping) {
            if ($availableTopping['value'] === $toppingValue) {
                $toppingPrice = $availableTopping['price'];
                $toppingTotal += $toppingPrice * $toppingQuantity;
                break;
            }
        }
    }
}

// Tính tổng giá đồ uống kèm
$extraWaterTotal = 0;
if (!empty($product['extraWater']) && !empty($extraWaters)) {
    foreach ($extraWaters as $waterItem) {
        $waterValue = $waterItem['value'];
        $waterQuantity = intval($waterItem['quantity']);
        
        // Tìm đồ uống kèm trong danh sách
        foreach ($product['extraWater'] as $availableWater) {
            if ($availableWater['name'] === $waterValue) {
                $waterPrice = $availableWater['price'];
                $extraWaterTotal += $waterPrice * $waterQuantity;
                break;
            }
        }
    }
}

// Tính tổng giá cuối cùng
$finalPrice = $productTotal + $toppingTotal + $extraWaterTotal;

// Định dạng giá
$formattedPrice = number_format($finalPrice, 0, '', ',') . '₫';

// Trả về kết quả
echo json_encode([
    'basePrice' => $basePrice,
    'productTotal' => $productTotal,
    'toppingTotal' => $toppingTotal,
    'extraWaterTotal' => $extraWaterTotal,
    'finalPrice' => $finalPrice,
    'formattedPrice' => $formattedPrice
]);
?>