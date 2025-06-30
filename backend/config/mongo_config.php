<?php
if (!defined('MONGO_HOST')) define('MONGO_HOST', 'localhost');
if (!defined('MONGO_PORT')) define('MONGO_PORT', 27017);
if (!defined('MONGO_DATABASE')) define('MONGO_DATABASE', 'bostarter_logs');
if (!defined('MONGO_USERNAME')) define('MONGO_USERNAME', ''); 
if (!defined('MONGO_PASSWORD')) define('MONGO_PASSWORD', ''); 
$mongoConfig = [
    'connection_string' => 'mongodb:
    'database' => MONGO_DATABASE,
    'fallback_log_path' => __DIR__ . '/../logs/mongodb_fallback.log',
    'options' => [
        'serverSelectionTimeoutMS' => 3000,
        'connectTimeoutMS' => 5000,
        'socketTimeoutMS' => 30000
    ]
];
if (!empty(MONGO_USERNAME) && !empty(MONGO_PASSWORD)) {
    $mongoConfig['connection_string'] = 'mongodb:
}
if (!function_exists('getMongoConnection')) {
    function getMongoConnection() {
        try {
            $connectionString = "mongodb:
            if (!empty(MONGO_USERNAME) && !empty(MONGO_PASSWORD)) {
                $connectionString = "mongodb:
            }
            $client = new MongoDB\Client($connectionString);
            $database = $client->selectDatabase(MONGO_DATABASE);
            return $database;
        } catch (Exception $e) {
            error_log("MongoDB connection failed: " . $e->getMessage());
            throw new Exception("MongoDB connection failed");
        }
    }
}
if (!function_exists('logToMongo')) {
    function logToMongo($collection, $data) {
        try {
            $db = getMongoConnection();
            $coll = $db->selectCollection($collection);
            $data['timestamp'] = new MongoDB\BSON\UTCDateTime();
            $result = $coll->insertOne($data);
            return $result->getInsertedId();
        } catch (Exception $e) {
            error_log("MongoDB logging failed: " . $e->getMessage());
            return false;
        }
    }
}
if (!function_exists('getLogsFromMongo')) {
    function getLogsFromMongo($collection, $filter = [], $limit = 100, $sort = ['timestamp' => -1]) {
        try {
            $db = getMongoConnection();
            $coll = $db->selectCollection($collection);
            $options = [
                'limit' => $limit,
                'sort' => $sort
            ];
            $cursor = $coll->find($filter, $options);
            return $cursor->toArray();
        } catch (Exception $e) {
            error_log("MongoDB read failed: " . $e->getMessage());
            return [];
        }
    }
}
if (!defined('MONGO_COLLECTION_EVENTS')) define('MONGO_COLLECTION_EVENTS', 'events');
if (!defined('MONGO_COLLECTION_ERRORS')) define('MONGO_COLLECTION_ERRORS', 'errors');
if (!defined('MONGO_COLLECTION_PERFORMANCE')) define('MONGO_COLLECTION_PERFORMANCE', 'performance');
if (!defined('MONGO_COLLECTION_SECURITY')) define('MONGO_COLLECTION_SECURITY', 'security');
if (!defined('MONGO_COLLECTION_USER_ACTIONS')) define('MONGO_COLLECTION_USER_ACTIONS', 'user_actions');
return $mongoConfig;
?>
