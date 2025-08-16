<?php
header('Content-Type: application/json');

// Database configuration
require_once 'db_config.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }

    $result = $conn->query("SELECT name, email, testimonial FROM testimonials ORDER BY created_at DESC LIMIT 4");
    
    if (!$result) {
        throw new Exception("Failed to fetch testimonials");
    }
    
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($conn)) $conn->close();
}
?>