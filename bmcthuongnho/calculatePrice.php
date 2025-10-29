<?php
header('Content-Type: application/json');

// Path to the JSON file containing product data
$jsonFilePath = 'products/productData.json';

// Get data from request
$productSku = isset($_POST['productSku']) ? $_POST['productSku'] : '';
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
$toppingsJson = isset($_POST['toppings']) ? $_POST['toppings'] : '{}';
$drinksJson = isset($_POST['drinks']) ? $_POST['drinks'] : '{}';
$selectedToppings = json_decode($toppingsJson, true) ?: [];
$selectedDrinks = json_decode($drinksJson, true) ?: [];

// Check if productSku is provided
if (empty($productSku)) {
    echo json_encode(['error' => 'Product SKU is required']);
    exit;
}

try {
    // Get product data from JSON file
    $jsonData = file_get_contents($jsonFilePath);
    $productsData = json_decode($jsonData, true);
    
    if (!$productsData) {
        throw new Exception("Không thể đọc dữ liệu sản phẩm");
    }
    
    // Find product by productSku
    $foundProduct = null;
    foreach ($productsData as $product) {
        if (isset($product['productSku']) && $product['productSku'] === $productSku) {
            $foundProduct = $product;
            break;
        }
    }
    
    if (!$foundProduct) {
        throw new Exception("Không tìm thấy sản phẩm với SKU: $productSku");
    }
    
    // Get base price of the product and convert to number
    $basePrice = intval(str_replace(['₫', ',', '.'], '', $foundProduct['productPrice']));
    
    // Calculate total price of selected toppings
    $toppingTotal = 0;
    if (!empty($selectedToppings) && isset($foundProduct['toppings'])) {
        foreach ($selectedToppings as $toppingItem) {
            if (isset($toppingItem['value']) && isset($toppingItem['quantity'])) {
                $toppingValue = $toppingItem['value'];
                $toppingQuantity = intval($toppingItem['quantity']);
                
                foreach ($foundProduct['toppings'] as $availableTopping) {
                    if ($availableTopping['value'] === $toppingValue) {
                        $toppingPrice = $availableTopping['price'];
                        $toppingTotal += $toppingPrice * $toppingQuantity;
                        break;
                    }
                }
            }
        }
    }
    
    // Calculate total price of selected drinks
    $drinkTotal = 0;
    if (!empty($selectedDrinks) && isset($foundProduct['extraWater'])) {
        foreach ($selectedDrinks as $drinkItem) {
            if (isset($drinkItem['value']) && isset($drinkItem['quantity'])) {
                $drinkValue = $drinkItem['value'];
                $drinkQuantity = intval($drinkItem['quantity']);
                
                foreach ($foundProduct['extraWater'] as $availableDrink) {
                    if ($availableDrink['name'] === $drinkValue) {
                        $drinkPrice = $availableDrink['price'];
                        $drinkTotal += $drinkPrice * $drinkQuantity;
                        break;
                    }
                }
            }
        }
    }
    
    // Calculate product price based on quantity
    $productPrice = $basePrice * $quantity;
    
    // Final total price
    $finalPrice = $productPrice + $toppingTotal + $drinkTotal;
    
    // Format price according to Vietnamese currency format
    $formattedPrice = number_format($finalPrice, 0, '', ',') . '₫';
    
    // Return the result with detailed information
    echo json_encode([
        'success' => true,
        'basePrice' => $basePrice,
        'formattedBasePrice' => number_format($basePrice, 0, '', ',') . '₫',
        'productPrice' => $productPrice,
        'formattedProductPrice' => number_format($productPrice, 0, '', ',') . '₫',
        'toppingTotal' => $toppingTotal,
        'formattedToppingTotal' => number_format($toppingTotal, 0, '', ',') . '₫',
        'drinkTotal' => $drinkTotal,
        'formattedDrinkTotal' => number_format($drinkTotal, 0, '', ',') . '₫',
        'finalPrice' => $finalPrice,
        'totalPrice' => $formattedPrice
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    error_log("Error in calculatePrice.php: " . $e->getMessage());
}
?>