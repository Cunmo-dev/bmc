<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Xử lý preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Chỉ chấp nhận POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Chỉ chấp nhận POST request'
    ]);
    exit();
}

require_once 'vendor/autoload.php'; // MongoDB PHP Driver

// Class xử lý MongoDB
class MongoDBHandler {
    private $client;
    private $database;
    
    public function __construct($connectionString, $databaseName) {
        try {
            $this->client = new MongoDB\Client($connectionString);
            $this->database = $this->client->selectDatabase($databaseName);
        } catch (Exception $e) {
            throw new Exception("Lỗi kết nối MongoDB: " . $e->getMessage());
        }
    }
    
    public function findOne($collectionName, $filter) {
        try {
            $collection = $this->database->selectCollection($collectionName);
            return $collection->findOne($filter);
        } catch (Exception $e) {
            error_log("MongoDB Find Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateOne($collectionName, $filter, $update) {
        try {
            $collection = $this->database->selectCollection($collectionName);
            $result = $collection->updateOne($filter, $update);
            return $result->getModifiedCount() > 0;
        } catch (Exception $e) {
            error_log("MongoDB Update Error: " . $e->getMessage());
            return false;
        }
    }
}

try {
    // Đọc dữ liệu JSON từ request
    $jsonInput = file_get_contents('php://input');
    if (empty($jsonInput)) {
        throw new Exception('Không có dữ liệu được gửi');
    }
    
    $requestData = json_decode($jsonInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Dữ liệu JSON không hợp lệ: ' . json_last_error_msg());
    }
    
    // Validate dữ liệu bắt buộc
    $machineId = $requestData['MachineId'] ?? '';
    if (empty($machineId)) {
        throw new Exception('Thiếu Machine ID');
    }
    
    // Khởi tạo kết nối MongoDB
    $mongo = new MongoDBHandler(
        'mongodb+srv://cunmoPro:Thanhcong140421%40@cluster0.s2sz5zy.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0', 
        'Allin'
    );
    
    // Tìm yêu cầu license của machine này
    $request = $mongo->findOne('license', [
        'MachineId' => $machineId
    ]);
    
    if (!$request) {
        // Chưa có yêu cầu nào
        echo json_encode([
            'success' => false,
            'message' => 'Chưa có yêu cầu license cho machine này',
            'status' => 'no_request',
            'data' => null
        ]);
        exit();
    }
    
    // Kiểm tra trạng thái yêu cầu
    switch ($request['Status']) {
        case 'pending':
            echo json_encode([
                'success' => false,
                'message' => 'Yêu cầu license đang chờ xử lý',
                'status' => 'pending',
                'data' => [
                    'requestTime' => $request['RequestTime'],
                    'machineId' => $request['MachineId'],
                    'computerName' => $request['ComputerName']
                ]
            ]);
            break;
            
        case 'rejected':
            echo json_encode([
                'success' => false,
                'message' => 'Yêu cầu license đã bị từ chối',
                'status' => 'rejected',
                'data' => [
                    'processedAt' => isset($request['ProcessedAt']) ? 
                        $request['ProcessedAt']->toDateTime()->format('Y-m-d H:i:s') : null
                ]
            ]);
            break;
            
        case 'approved':
            // Kiểm tra license chi tiết
            $license = $mongo->findOne('licenses', [
                'MachineId' => $machineId,
                'IsActive' => true
            ]);
            
            if (!$license) {
                echo json_encode([
                    'success' => false,
                    'message' => 'License không tồn tại hoặc đã bị vô hiệu hóa',
                    'status' => 'no_license'
                ]);
                break;
            }
            
            // Kiểm tra hạn sử dụng
            $currentTime = new MongoDB\BSON\UTCDateTime();
            if ($license['ExpiryDate'] < $currentTime) {
                // License hết hạn, vô hiệu hóa
                $mongo->updateOne('licenses', 
                    ['MachineId' => $machineId],
                    ['$set' => ['IsActive' => false, 'DeactivatedAt' => $currentTime]]
                );
                
                echo json_encode([
                    'success' => false,
                    'message' => 'License đã hết hạn',
                    'status' => 'expired',
                    'data' => [
                        'expiryDate' => $license['ExpiryDate']->toDateTime()->format('Y-m-d H:i:s')
                    ]
                ]);
                break;
            }
            
            // License hợp lệ - cập nhật thông tin truy cập
            $mongo->updateOne('licenses',
                ['MachineId' => $machineId],
                ['$set' => [
                    'LastAccessAt' => $currentTime,
                    'ActivationCount' => ($license['ActivationCount'] ?? 0) + 1
                ]]
            );
            
            // Trả về thông tin license
            echo json_encode([
                'success' => true,
                'message' => 'License hợp lệ',
                'status' => 'valid',
                'data' => [
                    'licenseKey' => $license['LicenseKey'],
                    'isActive' => true,
                    'expiryDate' => $license['ExpiryDate']->toDateTime()->format('Y-m-d H:i:s'),
                    'activationCount' => ($license['ActivationCount'] ?? 0) + 1,
                    'activationLimit' => $license['ActivationLimit'] ?? 1,
                    'daysRemaining' => ceil(($license['ExpiryDate']->toDateTime()->getTimestamp() - time()) / (24 * 60 * 60)),
                    'computerName' => $license['ComputerName'],
                    'userName' => $license['UserName']
                ]
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Trạng thái yêu cầu không xác định',
                'status' => 'unknown'
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'status' => 'error'
    ]);
    error_log("Check License Error: " . $e->getMessage());
}
?>