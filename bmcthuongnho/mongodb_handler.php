<?php
/**
 * MongoDB CRUD Operations in PHP
 * Tập hợp đầy đủ các chức năng xử lý dữ liệu MongoDB sử dụng PHP MongoDB Library
 */

require_once __DIR__ . '/vendor/autoload.php'; // Cần cài đặt thư viện qua composer

use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\Regex;

class MongoDBHandler {
    private $client;
    private $database;
    
    /**
     * Khởi tạo kết nối với MongoDB
     * 
     * @param string $connectionString Chuỗi kết nối đến MongoDB (mặc định: mongodb://localhost:27017)
     * @param string $databaseName Tên database (mặc định: mydb)
     */
    public function __construct(string $connectionString = 'mongodb+srv://cunmoPro:Thanhcong140421%40@cluster0.s2sz5zy.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0', string $databaseName = 'Allin') {
        try {
            $this->client = new Client($connectionString);
            $this->database = $this->client->selectDatabase($databaseName);
        } catch (Exception $e) {
            die("Lỗi kết nối MongoDB: " . $e->getMessage());
        }
    }
    
    /**
     * Lấy danh sách tất cả collections trong database
     * 
     * @return array Danh sách collections
     */
    public function listCollections(): array {
        $collections = [];
        foreach ($this->database->listCollections() as $collection) {
            $collections[] = $collection->getName();
        }
        return $collections;
    }
    
    /**
     * Tạo collection mới
     * 
     * @param string $collectionName Tên collection muốn tạo
     * @param array $options Tùy chọn khi tạo collection (validation, ...)
     * @return bool True nếu tạo thành công
     */
    public function createCollection(string $collectionName, array $options = []): bool {
        try {
            $this->database->createCollection($collectionName, $options);
            return true;
        } catch (Exception $e) {
            echo "Lỗi tạo collection: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Xóa collection
     * 
     * @param string $collectionName Tên collection muốn xóa
     * @return bool True nếu xóa thành công
     */
    public function dropCollection(string $collectionName): bool {
        try {
            $this->database->dropCollection($collectionName);
            return true;
        } catch (Exception $e) {
            echo "Lỗi xóa collection: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    // ==== THAO TÁC CREATE ====
    
    /**
     * Thêm một document vào collection
     * 
     * @param string $collectionName Tên collection
     * @param array $document Dữ liệu document cần thêm
     * @return string|null ID của document vừa thêm hoặc null nếu có lỗi
     */
    public function insertOne(string $collectionName, array $document): ?string {
        try {
            $collection = $this->database->selectCollection($collectionName);
            $result = $collection->insertOne($document);
            
            if ($result->getInsertedCount() === 1) {
                return (string) $result->getInsertedId();
            }
            
            return null;
        } catch (Exception $e) {
            echo "Lỗi thêm document: " . $e->getMessage() . "\n";
            return null;
        }
    }
    
    /**
     * Thêm nhiều document vào collection
     * 
     * @param string $collectionName Tên collection
     * @param array $documents Mảng các document cần thêm
     * @return array|null Mảng ID của các document vừa thêm hoặc null nếu có lỗi
     */
    public function insertMany(string $collectionName, array $documents): ?array {
        try {
            $collection = $this->database->selectCollection($collectionName);
            $result = $collection->insertMany($documents);
            
            if ($result->getInsertedCount() > 0) {
                $insertedIds = [];
                foreach ($result->getInsertedIds() as $id) {
                    $insertedIds[] = (string) $id;
                }
                return $insertedIds;
            }
            
            return null;
        } catch (Exception $e) {
            echo "Lỗi thêm nhiều document: " . $e->getMessage() . "\n";
            return null;
        }
    }
    
    // ==== THAO TÁC READ ====
    
    /**
     * Tìm một document trong collection
     * 
     * @param string $collectionName Tên collection
     * @param array $filter Điều kiện tìm kiếm
     * @param array $options Tùy chọn (projection, sort, ...)
     * @return array|null Document tìm được hoặc null nếu không tìm thấy
     */
    public function findOne(string $collectionName, array $filter = [], array $options = []): ?array {
        try {
            $collection = $this->database->selectCollection($collectionName);
            $document = $collection->findOne($filter, $options);
            
            if ($document) {
                return $document->getArrayCopy();
            }
            
            return null;
        } catch (Exception $e) {
            echo "Lỗi tìm document: " . $e->getMessage() . "\n";
            return null;
        }
    }
    
    /**
     * Tìm document theo ID
     * 
     * @param string $collectionName Tên collection
     * @param string $id ID của document cần tìm
     * @param array $options Tùy chọn (projection, ...)
     * @return array|null Document tìm được hoặc null nếu không tìm thấy
     */
    public function findById(string $collectionName, string $id, array $options = []): ?array {
        try {
            $collection = $this->database->selectCollection($collectionName);
            $document = $collection->findOne(['_id' => new ObjectId($id)], $options);
            
            if ($document) {
                return $document->getArrayCopy();
            }
            
            return null;
        } catch (Exception $e) {
            echo "Lỗi tìm document theo ID: " . $e->getMessage() . "\n";
            return null;
        }
    }
    
    /**
     * Tìm nhiều document trong collection
     * 
     * @param string $collectionName Tên collection
     * @param array $filter Điều kiện tìm kiếm
     * @param array $options Tùy chọn (projection, sort, limit, skip,...)
     * @return array Mảng các document tìm được
     */
    public function find(string $collectionName, array $filter = [], array $options = []): array {
        try {
            $collection = $this->database->selectCollection($collectionName);
            $cursor = $collection->find($filter, $options);
            
            $documents = [];
            foreach ($cursor as $document) {
                $documents[] = $document->getArrayCopy();
            }
            
            return $documents;
        } catch (Exception $e) {
            echo "Lỗi tìm nhiều document: " . $e->getMessage() . "\n";
            return [];
        }
    }
    
    /**
     * Đếm số document trong collection theo điều kiện
     * 
     * @param string $collectionName Tên collection
     * @param array $filter Điều kiện đếm
     * @param array $options Tùy chọn
     * @return int Số document thỏa điều kiện
     */
    public function count(string $collectionName, array $filter = [], array $options = []): int {
        try {
            $collection = $this->database->selectCollection($collectionName);
            return $collection->countDocuments($filter, $options);
        } catch (Exception $e) {
            echo "Lỗi đếm document: " . $e->getMessage() . "\n";
            return 0;
        }
    }
    
    // ==== THAO TÁC UPDATE ====
    
    /**
     * Cập nhật một document trong collection
     * 
     * @param string $collectionName Tên collection
     * @param array $filter Điều kiện tìm document cần cập nhật
     * @param array $update Nội dung cập nhật ($set, $unset, $inc...)
     * @param array $options Tùy chọn (upsert, ...)
     * @return bool True nếu cập nhật thành công
     */
    public function updateOne(string $collectionName, array $filter, array $update, array $options = []): bool {
        try {
            $collection = $this->database->selectCollection($collectionName);
            $result = $collection->updateOne($filter, $update, $options);
            
            return ($result->getModifiedCount() > 0 || $result->getUpsertedCount() > 0);
        } catch (Exception $e) {
            echo "Lỗi cập nhật document: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Cập nhật một document theo ID
     * 
     * @param string $collectionName Tên collection
     * @param string $id ID của document cần cập nhật
     * @param array $update Nội dung cập nhật ($set, $unset, $inc...)
     * @param array $options Tùy chọn (upsert, ...)
     * @return bool True nếu cập nhật thành công
     */
    public function updateById(string $collectionName, string $id, array $update, array $options = []): bool {
        try {
            $collection = $this->database->selectCollection($collectionName);
            $result = $collection->updateOne(
                ['_id' => new ObjectId($id)],
                $update,
                $options
            );
            
            return ($result->getModifiedCount() > 0);
        } catch (Exception $e) {
            echo "Lỗi cập nhật document theo ID: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Cập nhật nhiều document trong collection
     * 
     * @param string $collectionName Tên collection
     * @param array $filter Điều kiện tìm document cần cập nhật
     * @param array $update Nội dung cập nhật ($set, $unset, $inc...)
     * @param array $options Tùy chọn
     * @return int Số document đã cập nhật
     */
    public function updateMany(string $collectionName, array $filter, array $update, array $options = []): int {
        try {
            $collection = $this->database->selectCollection($collectionName);
            $result = $collection->updateMany($filter, $update, $options);
            
            return $result->getModifiedCount();
        } catch (Exception $e) {
            echo "Lỗi cập nhật nhiều document: " . $e->getMessage() . "\n";
            return 0;
        }
    }
    
    /**
     * Thay thế hoàn toàn một document
     * 
     * @param string $collectionName Tên collection
     * @param array $filter Điều kiện tìm document cần thay thế
     * @param array $replacement Document mới thay thế hoàn toàn
     * @param array $options Tùy chọn (upsert, ...)
     * @return bool True nếu thay thế thành công
     */
    public function replaceOne(string $collectionName, array $filter, array $replacement, array $options = []): bool {
        try {
            $collection = $this->database->selectCollection($collectionName);
            $result = $collection->replaceOne($filter, $replacement, $options);
            
            return ($result->getModifiedCount() > 0 || $result->getUpsertedCount() > 0);
        } catch (Exception $e) {
            echo "Lỗi thay thế document: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    // ==== THAO TÁC DELETE ====
    
    /**
     * Xóa một document trong collection
     * 
     * @param string $collectionName Tên collection
     * @param array $filter Điều kiện tìm document cần xóa
     * @param array $options Tùy chọn
     * @return bool True nếu xóa thành công
     */
    public function deleteOne(string $collectionName, array $filter, array $options = []): bool {
        try {
            $collection = $this->database->selectCollection($collectionName);
            $result = $collection->deleteOne($filter, $options);
            
            return ($result->getDeletedCount() > 0);
        } catch (Exception $e) {
            echo "Lỗi xóa document: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Xóa một document theo ID
     * 
     * @param string $collectionName Tên collection
     * @param string $id ID của document cần xóa
     * @param array $options Tùy chọn
     * @return bool True nếu xóa thành công
     */
    public function deleteById(string $collectionName, string $id, array $options = []): bool {
        try {
            $collection = $this->database->selectCollection($collectionName);
            $result = $collection->deleteOne(
                ['_id' => new ObjectId($id)],
                $options
            );
            
            return ($result->getDeletedCount() > 0);
        } catch (Exception $e) {
            echo "Lỗi xóa document theo ID: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Xóa nhiều document trong collection
     * 
     * @param string $collectionName Tên collection
     * @param array $filter Điều kiện tìm document cần xóa
     * @param array $options Tùy chọn
     * @return int Số document đã xóa
     */
    public function deleteMany(string $collectionName, array $filter, array $options = []): int {
        try {
            $collection = $this->database->selectCollection($collectionName);
            $result = $collection->deleteMany($filter, $options);
            
            return $result->getDeletedCount();
        } catch (Exception $e) {
            echo "Lỗi xóa nhiều document: " . $e->getMessage() . "\n";
            return 0;
        }
    }
    
    // ==== THAO TÁC NÂNG CAO ====
    
    /**
     * Thực hiện aggregation pipeline
     * 
     * @param string $collectionName Tên collection
     * @param array $pipeline Mảng các stage của aggregation pipeline
     * @param array $options Tùy chọn
     * @return array Kết quả aggregation
     */
    public function aggregate(string $collectionName, array $pipeline, array $options = []): array {
        try {
            $collection = $this->database->selectCollection($collectionName);
            $cursor = $collection->aggregate($pipeline, $options);
            
            $results = [];
            foreach ($cursor as $document) {
                $results[] = $document->getArrayCopy();
            }
            
            return $results;
        } catch (Exception $e) {
            echo "Lỗi thực hiện aggregation: " . $e->getMessage() . "\n";
            return [];
        }
    }
    
    /**
     * Tạo chỉ mục cho collection
     * 
     * @param string $collectionName Tên collection
     * @param array $keys Các trường cần đánh index
     * @param array $options Tùy chọn (unique, ...)
     * @return string|null Tên index đã tạo hoặc null nếu có lỗi
     */
    public function createIndex(string $collectionName, array $keys, array $options = []): ?string {
        try {
            $collection = $this->database->selectCollection($collectionName);
            return $collection->createIndex($keys, $options);
        } catch (Exception $e) {
            echo "Lỗi tạo index: " . $e->getMessage() . "\n";
            return null;
        }
    }
    
    /**
     * Lấy danh sách các index của collection
     * 
     * @param string $collectionName Tên collection
     * @return array Danh sách các index
     */
    public function listIndexes(string $collectionName): array {
        try {
            $collection = $this->database->selectCollection($collectionName);
            $cursor = $collection->listIndexes();
            
            $indexes = [];
            foreach ($cursor as $index) {
                $indexes[] = $index->getArrayCopy();
            }
            
            return $indexes;
        } catch (Exception $e) {
            echo "Lỗi lấy danh sách index: " . $e->getMessage() . "\n";
            return [];
        }
    }
    
    /**
     * Xóa một index của collection
     * 
     * @param string $collectionName Tên collection
     * @param string $indexName Tên index cần xóa
     * @return bool True nếu xóa thành công
     */
    public function dropIndex(string $collectionName, string $indexName): bool {
        try {
            $collection = $this->database->selectCollection($collectionName);
            $collection->dropIndex($indexName);
            return true;
        } catch (Exception $e) {
            echo "Lỗi xóa index: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Xóa tất cả index của collection (trừ _id)
     * 
     * @param string $collectionName Tên collection
     * @return bool True nếu xóa thành công
     */
    public function dropAllIndexes(string $collectionName): bool {
        try {
            $collection = $this->database->selectCollection($collectionName);
            $collection->dropIndexes();
            return true;
        } catch (Exception $e) {
            echo "Lỗi xóa tất cả index: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Thực hiện bulk write (nhiều thao tác write trong một lần gửi)
     * 
     * @param string $collectionName Tên collection
     * @param array $operations Mảng các thao tác (insertOne, updateOne, deleteOne,...)
     * @param array $options Tùy chọn
     * @return array Kết quả thực hiện các thao tác
     */
    public function bulkWrite(string $collectionName, array $operations, array $options = []): array {
        try {
            $collection = $this->database->selectCollection($collectionName);
            $result = $collection->bulkWrite($operations, $options);
            
            return [
                'insertedCount' => $result->getInsertedCount(),
                'matchedCount' => $result->getMatchedCount(),
                'modifiedCount' => $result->getModifiedCount(),
                'deletedCount' => $result->getDeletedCount(),
                'upsertedCount' => $result->getUpsertedCount(),
                'upsertedIds' => $result->getUpsertedIds(),
            ];
        } catch (Exception $e) {
            echo "Lỗi thực hiện bulk write: " . $e->getMessage() . "\n";
            return [];
        }
    }
    
    /**
     * Tạo và trả về đối tượng BSON UTCDateTime từ timestamp PHP
     * 
     * @param int|null $timestamp Unix timestamp (để null để dùng thời gian hiện tại)
     * @return UTCDateTime Đối tượng UTCDateTime của MongoDB
     */
    public function createUTCDateTime(?int $timestamp = null): UTCDateTime {
        if ($timestamp === null) {
            $timestamp = time();
        }
        return new UTCDateTime($timestamp * 1000);
    }
    
    /**
     * Tạo và trả về đối tượng BSON ObjectId từ string
     * 
     * @param string|null $id Chuỗi ID (để null để tạo ID mới)
     * @return ObjectId Đối tượng ObjectId của MongoDB
     */
    public function createObjectId(?string $id = null): ObjectId {
        return $id ? new ObjectId($id) : new ObjectId();
    }
    
    /**
     * Tạo đối tượng Regex cho tìm kiếm
     * 
     * @param string $pattern Mẫu regex
     * @param string $flags Cờ (i: không phân biệt hoa thường, m: đa dòng, ...)
     * @return Regex Đối tượng Regex của MongoDB
     */
    public function createRegex(string $pattern, string $flags = ''): Regex {
        return new Regex($pattern, $flags);
    }
    
    /**
     * Tìm kiếm văn bản đầy đủ (yêu cầu text index)
     * 
     * @param string $collectionName Tên collection
     * @param string $text Văn bản cần tìm kiếm
     * @param array $options Tùy chọn tìm kiếm
     * @return array Kết quả tìm kiếm
     */
    public function textSearch(string $collectionName, string $text, array $options = []): array {
        try {
            $collection = $this->database->selectCollection($collectionName);
            $filter = ['$text' => ['$search' => $text]];
            
            // Thêm score để sắp xếp theo độ liên quan
            if (!isset($options['projection'])) {
                $options['projection'] = [];
            }
            $options['projection']['score'] = ['$meta' => 'textScore'];
            
            if (!isset($options['sort'])) {
                $options['sort'] = [];
            }
            $options['sort']['score'] = ['$meta' => 'textScore'];
            
            $cursor = $collection->find($filter, $options);
            
            $results = [];
            foreach ($cursor as $document) {
                $results[] = $document->getArrayCopy();
            }
            
            return $results;
        } catch (Exception $e) {
            echo "Lỗi tìm kiếm văn bản: " . $e->getMessage() . "\n";
            return [];
        }
    }
    
    /**
     * Thực hiện transaction (cần MongoDB 4.0+ và replica set)
     * 
     * @param callable $callback Hàm xử lý trong transaction
     * @param array $options Tùy chọn
     * @return mixed Kết quả từ callback
     */
    public function executeTransaction(callable $callback, array $options = []) {
        try {
            $session = $this->client->startSession();
            $result = $session->withTransaction($callback, $options);
            $session->endSession();
            return $result;
        } catch (Exception $e) {
            echo "Lỗi thực hiện transaction: " . $e->getMessage() . "\n";
            return null;
        }
    }
}

// ===== HƯỚNG DẪN SỬ DỤNG =====

/**
 * Trước tiên, cài đặt thư viện PHP MongoDB qua Composer:
 * composer require mongodb/mongodb
 */

// Khởi tạo đối tượng handler
// $mongo = new MongoDBHandler('mongodb://username:password@host:port', 'tên_database');
// $mongo = new MongoDBHandler('mongodb://localhost:27017', 'mydb');

// Ví dụ sử dụng:
/**
// Tạo collection
$mongo->createCollection('users', [
    'validator' => [
        '$jsonSchema' => [
            'bsonType' => 'object',
            'required' => ['name', 'email'],
            'properties' => [
                'name' => ['bsonType' => 'string'],
                'email' => ['bsonType' => 'string'],
                'age' => ['bsonType' => 'int']
            ]
        ]
    ]
]);

// Thêm dữ liệu
$userId = $mongo->insertOne('users', [
    'name' => 'Nguyễn Văn A',
    'email' => 'example@gmail.com',
    'age' => 30,
    'created_at' => $mongo->createUTCDateTime()
]);

// Thêm nhiều document
$mongo->insertMany('users', [
    [
        'name' => 'Trần Văn B',
        'email' => 'tranb@example.com',
        'age' => 25
    ],
    [
        'name' => 'Lê Thị C',
        'email' => 'lec@example.com',
        'age' => 28
    ]
]);

// Tìm kiếm
$user = $mongo->findById('users', $userId);
$youngUsers = $mongo->find('users', ['age' => ['$lt' => 30]]);

// Đếm
$count = $mongo->count('users', ['age' => ['$gte' => 25]]);

// Cập nhật
$mongo->updateOne('users', 
    ['email' => 'example@gmail.com'], 
    ['$set' => ['age' => 31, 'updated_at' => $mongo->createUTCDateTime()]]
);

// Cập nhật nhiều
$mongo->updateMany('users', 
    ['age' => ['$lt' => 30]], 
    ['$inc' => ['age' => 1]]
);

// Xóa
$mongo->deleteOne('users', ['email' => 'lec@example.com']);

// Aggregation
$result = $mongo->aggregate('users', [
    ['$match' => ['age' => ['$gte' => 25]]],
    ['$group' => [
        '_id' => null,
        'avg_age' => ['$avg' => '$age'],
        'count' => ['$sum' => 1]
    ]]
]);

// Tạo index
$mongo->createIndex('users', ['email' => 1], ['unique' => true]);
$mongo->createIndex('users', ['name' => 'text'], []); // Text index

// Tìm kiếm văn bản
$results = $mongo->textSearch('users', 'Nguyễn');

// Transaction (cần MongoDB 4.0+ và replica set)
$mongo->executeTransaction(function($session) use ($mongo) {
    $mongo->insertOne('accounts', 
        ['owner' => 'Alice', 'balance' => 100], 
        ['session' => $session]
    );
    
    $mongo->insertOne('transfers', 
        ['from' => 'Alice', 'to' => 'Bob', 'amount' => 50], 
        ['session' => $session]
    );
    
    $mongo->updateOne('accounts', 
        ['owner' => 'Alice'], 
        ['$inc' => ['balance' => -50]], 
        ['session' => $session]
    );
});
*/
?>