<?php
/**
 * MongoDB Configuration File
 * BOSTARTER - Crowdfunding Platform
 */

// MongoDB connection parameters
define('MONGO_HOST', 'localhost');
define('MONGO_PORT', 27017);
define('MONGO_DATABASE', 'bostarter_logs');
define('MONGO_USERNAME', ''); // Set if authentication is enabled
define('MONGO_PASSWORD', ''); // Set if authentication is enabled

// MongoDB connection function
function getMongoConnection() {
    try {
        // Build connection string
        $connectionString = "mongodb://" . MONGO_HOST . ":" . MONGO_PORT;
        
        // Add authentication if credentials are provided
        if (!empty(MONGO_USERNAME) && !empty(MONGO_PASSWORD)) {
            $connectionString = "mongodb://" . MONGO_USERNAME . ":" . MONGO_PASSWORD . "@" . MONGO_HOST . ":" . MONGO_PORT;
        }
        
        // Create MongoDB client
        $client = new MongoDB\Client($connectionString);
        
        // Select database
        $database = $client->selectDatabase(MONGO_DATABASE);
        
        return $database;
        
    } catch (Exception $e) {
        error_log("MongoDB connection failed: " . $e->getMessage());
        throw new Exception("MongoDB connection failed");
    }
}

// Log to MongoDB
function logToMongo($collection, $data) {
    try {
        $db = getMongoConnection();
        $coll = $db->selectCollection($collection);
        
        // Add timestamp
        $data['timestamp'] = new MongoDB\BSON\UTCDateTime();
        
        $result = $coll->insertOne($data);
        return $result->getInsertedId();
        
    } catch (Exception $e) {
        error_log("MongoDB logging failed: " . $e->getMessage());
        return false;
    }
}

// Get logs from MongoDB
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

// Collection names
define('MONGO_COLLECTION_EVENTS', 'events');
define('MONGO_COLLECTION_ERRORS', 'errors');
define('MONGO_COLLECTION_PERFORMANCE', 'performance');
define('MONGO_COLLECTION_SECURITY', 'security');
define('MONGO_COLLECTION_USER_ACTIONS', 'user_actions');
?>
