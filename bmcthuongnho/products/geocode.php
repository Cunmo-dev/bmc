<?php
// geocode.php - Script để lấy tọa độ từ địa chỉ sử dụng HERE Geocoding API
header('Content-Type: application/json');

// Kiểm tra nếu yêu cầu là POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Please use POST.']);
    exit;
}

// Kiểm tra dữ liệu đầu vào
$input = json_decode(file_get_contents('php://input'), true);

// Nếu không tìm thấy dữ liệu JSON, kiểm tra form data
if (!$input || !isset($input['address'])) {
    $address = isset($_POST['address']) ? $_POST['address'] : null;
    if (!$address) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing address parameter']);
        exit;
    }
} else {
    $address = $input['address'];
}

// Làm sạch và validate địa chỉ
$address = trim($address);
if (empty($address)) {
    http_response_code(400);
    echo json_encode(['error' => 'Address cannot be empty']);
    exit;
}

// Thêm "Hà Nội" vào địa chỉ nếu chưa có
if (stripos($address, 'hà nội') === false && stripos($address, 'ha noi') === false) {
    $address .= ', Hà Nội, Việt Nam';
}

// API key được lưu trên server (KHÔNG hiển thị trên client)
$apiKey = "bP21pMUDywjPLVeX3UsuTDClbbBvWg4gvss4dvhh7as";

// Chuẩn bị tham số truy vấn
$query = urlencode($address);

// URL endpoint của HERE Geocoding API với tham số giới hạn trong Hà Nội
$url = "https://geocode.search.hereapi.com/v1/geocode?q=$query&apiKey=$apiKey&in=countryCode:VNM";

// Thực hiện truy vấn API bằng cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'GeocodingApp/1.0');

// Thực thi cURL và kiểm tra lỗi
$response = curl_exec($ch);
if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(['error' => 'Curl error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

// Kiểm tra mã trạng thái HTTP
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200) {
    http_response_code($httpCode);
    echo json_encode(['error' => 'HERE API returned status code: ' . $httpCode]);
    exit;
}

// Phân tích kết quả JSON
$result = json_decode($response, true);

// Kiểm tra xem có kết quả không
if (!isset($result['items']) || empty($result['items'])) {
    echo json_encode([
        'status' => 'ZERO_RESULTS',
        'message' => 'No coordinates found for this address',
        'input' => $address,
        'coordinates' => null
    ]);
    exit;
}

// Lọc kết quả để chỉ lấy địa chỉ thuộc Hà Nội
$hanoi_results = array_filter($result['items'], function($item) {
    // Kiểm tra xem địa chỉ có chứa "Hà Nội" hoặc các thông tin liên quan không
    if (isset($item['address'])) {
        $addressData = $item['address'];
        
        // Kiểm tra thành phố
        if (isset($addressData['city'])) {
            $city = strtolower($addressData['city']);
            if (strpos($city, 'hà nội') !== false || strpos($city, 'ha noi') !== false || strpos($city, 'hanoi') !== false) {
                return true;
            }
        }
        
        // Kiểm tra trong label (mô tả đầy đủ)
        if (isset($addressData['label'])) {
            $label = strtolower($addressData['label']);
            if (strpos($label, 'hà nội') !== false || strpos($label, 'ha noi') !== false || strpos($label, 'hanoi') !== false) {
                return true;
            }
        }
        
        // Kiểm tra trong quận/huyện nếu là các quận/huyện của Hà Nội
        if (isset($addressData['district'])) {
            $district = strtolower($addressData['district']);
            $hanoi_districts = [
                'ba đình', 'hoàn kiếm', 'hai bà trưng', 'đống đa', 'tây hồ', 'cầu giấy', 
                'thanh xuân', 'hoàng mai', 'long biên', 'nam từ liêm', 'bắc từ liêm', 
                'hà đông', 'sơn tây', 'ba vì', 'phúc thọ', 'đan phượng', 'hoài đức', 
                'quốc oai', 'thạch thất', 'chương mỹ', 'thanh oai', 'thường tín', 
                'phú xuyên', 'ứng hòa', 'mỹ đức', 'thanh trì', 'gia lâm', 'đông anh', 
                'sóc sơn', 'mê linh'
            ];
            
            foreach ($hanoi_districts as $hanoi_district) {
                if (strpos($district, $hanoi_district) !== false) {
                    return true;
                }
            }
        }
    }
    return false;
});

// Nếu không có kết quả nào thuộc Hà Nội
if (empty($hanoi_results)) {
    echo json_encode([
        'status' => 'ZERO_RESULTS',
        'message' => 'No coordinates found in Hanoi for this address',
        'input' => $address,
        'coordinates' => null
    ]);
    exit;
}

// Sắp xếp lại mảng để bắt đầu từ index 0
$hanoi_results = array_values($hanoi_results);

// Lấy tọa độ từ kết quả đầu tiên thuộc Hà Nội
$firstResult = $hanoi_results[0];
$coordinates = [
    'lat' => isset($firstResult['position']['lat']) ? $firstResult['position']['lat'] : null,
    'lng' => isset($firstResult['position']['lng']) ? $firstResult['position']['lng'] : null
];

function calculateDistance($startLat, $startLng, $endLat, $endLng) {
    $url = "https://routing.openstreetmap.de/routed-car/route/v1/driving/$startLng,$startLat;$endLng,$endLat?overview=false&geometries=polyline&steps=true";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($err) {
        return ['error' => 'cURL Error: ' . $err];
    }
    
    if ($httpCode != 200) {
        return ['error' => 'OpenStreetMap API returned status code: ' . $httpCode];
    }
    
    $result = json_decode($response, true);
    
    if (!isset($result['routes']) || empty($result['routes'])) {
        return ['error' => 'No route found'];
    }
    
    $route = $result['routes'][0];
    return [
        'status' => 'OK',
        'distance' => $route['distance'], // Distance in meters
        'duration' => $route['duration']  // Duration in seconds
    ];
}

$storesFile = 'toado.json';

if (!file_exists($storesFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'Stores coordinates file not found']);
    exit;
}

$stores = json_decode(file_get_contents($storesFile), true);

if (!$stores) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid stores coordinates file']);
    exit;
}

// Calculate distance to each store
$results = [];
foreach ($stores as $storeId => $storeCoordinates) {
    $distance = calculateDistance(
        $coordinates['lat'],
        $coordinates['lng'],
        $storeCoordinates['lat'],
        $storeCoordinates['lng']
    );
    
    if (isset($distance['error'])) {
        $results[$storeId] = [
            'status' => 'ERROR',
            'error_message' => $distance['error']
        ];
    } else {
        $results[$storeId] = [
            'status' => 'OK',
            'distance_meters' => $distance['distance'],
            'distance_km' => round($distance['distance'] / 1000, 2),
        ];
    }
}

// Sắp xếp cửa hàng theo khoảng cách gần nhất
uasort($results, function($a, $b) {
    if (isset($a['distance_meters']) && isset($b['distance_meters'])) {
        return $a['distance_meters'] - $b['distance_meters'];
    }
    return 0;
});

// Tìm cửa hàng gần nhất
$closest_store = array_key_first($results);

// Chuẩn bị dữ liệu trả về
$output = [
    'status' => 'OK',
    'input' => $address,
    'formatted_address' => isset($firstResult['address']['label']) ? $firstResult['address']['label'] : '',
    'coordinates' => $coordinates,
    'store_distances' => $results,
    'closest_store' => $closest_store
];

// Trả về kết quả dưới dạng JSON
echo json_encode($output, JSON_PRETTY_PRINT);
?>