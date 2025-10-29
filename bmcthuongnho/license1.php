<?php
header('Content-Type: application/json; text/html; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');


// Xử lý preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
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
    
    public function insertOne($collectionName, $data) {
        try {
            $collection = $this->database->selectCollection($collectionName);
            $result = $collection->insertOne($data);
            return $result->getInsertedId();
        } catch (Exception $e) {
            error_log("MongoDB Insert Error: " . $e->getMessage());
            return false;
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
    
    public function find($collectionName, $filter = [], $options = []) {
        try {
            $collection = $this->database->selectCollection($collectionName);
            return $collection->find($filter, $options);
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

// Class tạo License
class LicenseGenerator {
    public static function generateLicenseKey($machineId, $computerName) {
        // Tạo license key dựa trên machine ID và thời gian
        $timestamp = time();
        $randomString = bin2hex(random_bytes(8));
        $baseString = $machineId . $computerName . $timestamp . $randomString;
        
        // Hash và format thành license key
        $hash = hash('sha256', $baseString);
        $licenseKey = strtoupper(substr($hash, 0, 8) . '-' . 
                                 substr($hash, 8, 8) . '-' . 
                                 substr($hash, 16, 8) . '-' . 
                                 substr($hash, 24, 8));
        
        return $licenseKey;
    }
    
    public static function generateExpiryDate($months = 12) {
        return new MongoDB\BSON\UTCDateTime((time() + ($months * 30 * 24 * 60 * 60)) * 1000);
    }
}

// Helper function để kiểm tra ObjectId hợp lệ
function isValidObjectId($id) {
    if (strlen($id) !== 24) {
        return false;
    }
    return ctype_xdigit($id);
}

try {
    // Khởi tạo kết nối MongoDB
    $mongo = new MongoDBHandler(
        'mongodb+srv://cunmoPro:Thanhcong140421%40@cluster0.s2sz5zy.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0', 
        'Allin'
    );
    
    // Xử lý các request khác nhau
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        handleGetRequest($mongo);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        handlePostRequest($mongo);
    } else {
        throw new Exception('Method không được hỗ trợ');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    error_log("License API Error: " . $e->getMessage());
}

function handleGetRequest($mongo) {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_requests':
            getAllRequests($mongo);
            break;
            
        case 'check_license':
            checkLicense($mongo);
            break;
            
        default:
            throw new Exception('Action không hợp lệ');
    }
}

function handlePostRequest($mongo) {
    $jsonInput = file_get_contents('php://input');
    $requestData = json_decode($jsonInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Dữ liệu JSON không hợp lệ: ' . json_last_error_msg());
    }

    $action = $requestData['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'process_request':
            processLicenseRequest($mongo, $requestData);
            break;
        case 'request':
            createLicenseRequest($mongo, $requestData);
            break;
        default:
            throw new Exception('Action không hợp lệ');
    }
}

function createLicenseRequest($mongo, $requestData) {
    $machineId = $requestData['MachineId'] ?? '';
    $computerName = $requestData['ComputerName'] ?? '';
    $userName = $requestData['UserName'] ?? '';
    $requestTime = $requestData['RequestTime'] ?? '';

    if (empty($machineId) || empty($computerName) || empty($userName)) {
        throw new Exception('Thiếu thông tin bắt buộc');
    }

    $existingRequest = $mongo->findOne('license', ['MachineId' => $machineId]);
    if ($existingRequest && $existingRequest['Status'] === 'pending') {
        throw new Exception('Yêu cầu đang chờ xử lý');
    }

    $data = [
        'MachineId' => $machineId,
        'ComputerName' => $computerName,
        'UserName' => $userName,
        'Status' => 'pending',
        'RequestTime' => new MongoDB\BSON\UTCDateTime(strtotime($requestTime) * 1000),
        'CreatedAt' => new MongoDB\BSON\UTCDateTime(),
        'IPAddress' => $_SERVER['REMOTE_ADDR']
    ];

    $result = $mongo->insertOne('license', $data);
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Yêu cầu license đã được gửi'
        ]);
    } else {
        throw new Exception('Lỗi khi tạo yêu cầu license');
    }
}

function getAllRequests($mongo) {
    try {
        $requests = $mongo->find('license', [], ['sort' => ['CreatedAt' => -1]]);
        
        // Kiểm tra nếu không có kết quả
        if (!$requests) {
            echo json_encode([
                'success' => true,
                'data' => [],
                'total' => 0,
                'message' => 'Không có dữ liệu'
            ]);
            return;
        }
        
        $requestsArray = [];
        foreach ($requests as $request) {
            // Chuyển đổi BSON Document thành PHP array bằng json_decode/encode
            $requestArray = json_decode(json_encode($request), true);
            
            // Chuyển đổi ObjectId thành string
            if (isset($requestArray['_id']['$oid'])) {
                $requestArray['_id'] = $requestArray['_id']['$oid'];
            }
            
            // Chuyển đổi MongoDB DateTime thành string
            $dateFields = ['CreatedAt', 'UpdatedAt', 'ProcessedAt', 'ExpiryDate', 'RequestTime'];
            
            foreach ($dateFields as $field) {
                if (isset($requestArray[$field])) {
                    if (is_array($requestArray[$field]) && isset($requestArray[$field]['$date'])) {
                        // MongoDB UTC DateTime format
                        if (isset($requestArray[$field]['$date']['$numberLong'])) {
                            $timestamp = (int)$requestArray[$field]['$date']['$numberLong'] / 1000;
                        } else {
                            $timestamp = strtotime($requestArray[$field]['$date']);
                        }
                        $requestArray[$field] = date('Y-m-d H:i:s', $timestamp);
                    } elseif (is_string($requestArray[$field])) {
                        // Nếu là string, thử parse thành DateTime
                        try {
                            $date = new DateTime($requestArray[$field]);
                            $requestArray[$field] = $date->format('Y-m-d H:i:s');
                        } catch (Exception $e) {
                            // Giữ nguyên nếu không parse được
                        }
                    }
                }
            }
            
            // Đảm bảo các field cần thiết tồn tại
            $requestArray['Status'] = $requestArray['Status'] ?? 'pending';
            $requestArray['IPAddress'] = $requestArray['IPAddress'] ?? 'N/A';
            $requestArray['UserAgent'] = $requestArray['UserAgent'] ?? 'N/A';
            $requestArray['RequestCount'] = $requestArray['RequestCount'] ?? 1;
            
            $requestsArray[] = $requestArray;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $requestsArray,
            'total' => count($requestsArray)
        ]);
        
    } catch (Exception $e) {
        error_log("getAllRequests Error: " . $e->getMessage());
        throw new Exception('Lỗi khi lấy danh sách yêu cầu: ' . $e->getMessage());
    }
}

function processLicenseRequest($mongo, $requestData) {
    $requestId = $requestData['requestId'] ?? '';
    $decision = $requestData['decision'] ?? '';
    
    if (empty($requestId) || empty($decision)) {
        throw new Exception('Thiếu thông tin requestId hoặc decision');
    }
    
    if (!in_array($decision, ['approve', 'reject'])) {
        throw new Exception('Decision không hợp lệ');
    }
    
    try {
        // Kiểm tra ObjectId hợp lệ bằng helper function
        if (!isValidObjectId($requestId)) {
            throw new Exception('Request ID không hợp lệ');
        }
        
        // Tạo ObjectId từ string
        $objectId = new MongoDB\BSON\ObjectId($requestId);
        
        // Tìm request
        $request = $mongo->findOne('license', ['_id' => $objectId]);
        
        if (!$request) {
            throw new Exception('Không tìm thấy yêu cầu');
        }
        
        // Chuyển đổi BSON Document thành array bằng json_decode/encode
        $requestArray = json_decode(json_encode($request), true);
        
        if ($requestArray['Status'] !== 'pending') {
            throw new Exception('Yêu cầu đã được xử lý trước đó');
        }
        
        $updateData = [
            'Status' => $decision === 'approve' ? 'approved' : 'rejected',
            'ProcessedAt' => new MongoDB\BSON\UTCDateTime(),
            'UpdatedAt' => new MongoDB\BSON\UTCDateTime()
        ];
        
        // Nếu chấp nhận, tạo license
        if ($decision === 'approve') {
            $licenseKey = LicenseGenerator::generateLicenseKey(
                $requestArray['MachineId'], 
                $requestArray['ComputerName']
            );
            
            $updateData['LicenseKey'] = $licenseKey;
            $updateData['ExpiryDate'] = LicenseGenerator::generateExpiryDate(12); // 12 tháng
            $updateData['IsActive'] = true;
            
            // Lưu license vào collection riêng
            $licenseData = [
                'RequestId' => $requestId,
                'MachineId' => $requestArray['MachineId'],
                'ComputerName' => $requestArray['ComputerName'],
                'UserName' => $requestArray['UserName'],
                'LicenseKey' => $licenseKey,
                'IsActive' => true,
                'CreatedAt' => new MongoDB\BSON\UTCDateTime(),
                'ExpiryDate' => $updateData['ExpiryDate'],
                'ActivationLimit' => 1, // Giới hạn kích hoạt
                'ActivationCount' => 0
            ];
            
            $mongo->insertOne('licenses', $licenseData);
        }
        
        // Cập nhật request
        $result = $mongo->updateOne(
            'license', 
            ['_id' => $objectId], 
            ['$set' => $updateData]
        );
        
        if ($result) {
            $message = $decision === 'approve' ? 
                'Yêu cầu đã được chấp nhận và license đã được tạo' : 
                'Yêu cầu đã được từ chối';
                
            echo json_encode([
                'success' => true,
                'message' => $message,
                'data' => [
                    'requestId' => $requestId,
                    'status' => $updateData['Status'],
                    'licenseKey' => $updateData['LicenseKey'] ?? null
                ]
            ]);
        } else {
            throw new Exception('Lỗi khi cập nhật trạng thái yêu cầu');
        }
        
    } catch (Exception $e) {
        error_log("processLicenseRequest Error: " . $e->getMessage());
        throw new Exception('Lỗi khi xử lý yêu cầu: ' . $e->getMessage());
    }
}

function checkLicense($mongo) {
    $machineId = $_GET['machineId'] ?? '';
    $licenseKey = $_GET['licenseKey'] ?? '';
    
    if (empty($machineId)) {
        throw new Exception('Thiếu Machine ID');
    }
    
    try {
        // Kiểm tra tất cả các trạng thái của machine ID
        $approvedRequest = $mongo->findOne('license', [
            'MachineId' => $machineId,
            'Status' => 'approved'
        ]);
        
        $pendingRequest = $mongo->findOne('license', [
            'MachineId' => $machineId,
            'Status' => 'pending'
        ]);
        
        $rejectedRequest = $mongo->findOne('license', [
            'MachineId' => $machineId,
            'Status' => 'rejected'
        ]);
        
        // Xử lý logic theo thứ tự ưu tiên
        if ($approvedRequest) {
            // Machine ID đã được approve
            return handleApprovedRequest($mongo, $approvedRequest, $machineId, $licenseKey);
            
        } elseif ($pendingRequest) {
            // Machine ID đang chờ duyệt
            echo json_encode([
                'success' => false,
                'message' => 'Yêu cầu đang chờ duyệt',
                'status' => 'PENDING'
            ]);
            return;
            
        } elseif ($rejectedRequest) {
            // Machine ID đã bị từ chối
            echo json_encode([
                'success' => false,
                'message' => 'Yêu cầu đã bị từ chối',
                'status' => 'REJECTED'
            ]);
            return;
            
        } else {
            // Machine ID chưa tồn tại - đây là lần đầu tiên
            // Tạo request mới với status pending
            $newRequest = [
                'MachineId' => $machineId,
                'Status' => 'pending',
                'RequestDate' => new MongoDB\BSON\UTCDateTime(),
                'CreatedAt' => new MongoDB\BSON\UTCDateTime()
            ];
            
            $mongo->insertOne('license', $newRequest);
            
            echo json_encode([
                'success' => false,
                'message' => 'Yêu cầu license mới đã được tạo và đang chờ duyệt',
                'status' => 'PENDING'
            ]);
            return;
        }
        
    } catch (Exception $e) {
        error_log("checkLicense Error: " . $e->getMessage());
        throw new Exception('Lỗi khi kiểm tra license: ' . $e->getMessage());
    }
}

function handleApprovedRequest($mongo, $approvedRequest, $machineId, $licenseKey) {
    // Chuyển đổi BSON Document thành array
    $requestArray = json_decode(json_encode($approvedRequest), true);
    
    // Nếu có license key, kiểm tra chi tiết
    if (!empty($licenseKey)) {
        $license = $mongo->findOne('licenses', [
            'MachineId' => $machineId,
            'LicenseKey' => $licenseKey,
            'IsActive' => true
        ]);
        
        if (!$license) {
            echo json_encode([
                'success' => false,
                'message' => 'License không hợp lệ hoặc đã bị vô hiệu hóa',
                'status' => 'invalid_license'
            ]);
            return;
        }
        
        $licenseArray = json_decode(json_encode($license), true);
        
        // Kiểm tra hạn sử dụng
        $currentTime = time() * 1000; // Convert to milliseconds
        $expiryTime = 0;
        
        if (isset($licenseArray['ExpiryDate']['$date']['$numberLong'])) {
            $expiryTime = (int)$licenseArray['ExpiryDate']['$date']['$numberLong'];
        }
        
        if ($expiryTime > 0 && $expiryTime < $currentTime) {
            echo json_encode([
                'success' => false,
                'message' => 'License đã hết hạn',
                'status' => 'expired',
                'expiryDate' => date('Y-m-d H:i:s', $expiryTime / 1000)
            ]);
            return;
        }
        
        // License hợp lệ
        echo json_encode([
            'success' => true,
            'message' => 'License hợp lệ',
            'status' => 'valid',
            'data' => [
                'licenseKey' => $licenseArray['LicenseKey'],
                'expiryDate' => $expiryTime > 0 ? date('Y-m-d H:i:s', $expiryTime / 1000) : null,
                'isActive' => $licenseArray['IsActive'],
                'activationCount' => $licenseArray['ActivationCount'],
                'activationLimit' => $licenseArray['ActivationLimit']
            ]
        ]);
        
    } else {
        // Chỉ kiểm tra request, trả về license nếu có
        $expiryDate = null;
        if (isset($requestArray['ExpiryDate']['$date']['$numberLong'])) {
            $expiryTime = (int)$requestArray['ExpiryDate']['$date']['$numberLong'];
            $expiryDate = date('Y-m-d H:i:s', $expiryTime / 1000);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Yêu cầu đã được chấp nhận',
            'status' => 'approved',
            'data' => [
                'licenseKey' => $requestArray['LicenseKey'] ?? null,
                'expiryDate' => $expiryDate
            ]
        ]);
    }
}
?>