<?php
header('Content-Type: application/json');

// Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'mbewereg_developer');
define('DB_PASS', 'devM@2024!!');
define('DB_NAME', 'mbewereg_lawdatabase');
define('ADMIN_EMAILS', 'admin@mbewegroup.co.za,athisiah@mbewegroup.co.za');
define('MAX_TESTIMONIAL_LENGTH', 1000);

// Validate input
if (empty($_POST['name']) || empty($_POST['testimonial'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Name and testimonial are required']));
}

if (strlen($_POST['testimonial']) > MAX_TESTIMONIAL_LENGTH) {
    http_response_code(400);
    die(json_encode(['error' => 'Testimonial too long']));
}

// Sanitize inputs
$name = htmlspecialchars(trim($_POST['name']));
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$testimonial = htmlspecialchars(trim($_POST['testimonial']));
$ip = $_SERVER['REMOTE_ADDR'];

try {
    // Database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }

    // Insert testimonial
    $stmt = $conn->prepare("INSERT INTO testimonials (name, email, testimonial, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $testimonial, $ip);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to save testimonial");
    }

    // Send email notification
    $to = ADMIN_EMAILS;
    $subject = "New Testimonial Submission: " . substr($testimonial, 0, 30) . "...";
    $message = "
    <html>
    <body>
        <h2>New Testimonial Received</h2>
        <p><strong>Name:</strong> $name</p>
        " . (!empty($email) ? "<p><strong>Email:</strong> $email</p>" : "") . "
        <p><strong>IP Address:</strong> $ip</p>
        <p><strong>Testimonial:</strong></p>
        <div style='border-left:3px solid #2c98f0;padding-left:15px;margin-left:10px;'>
            " . nl2br($testimonial) . "
        </div>
        <p style='margin-top:20px;font-size:0.9em;color:#666;'>
            This testimonial was automatically published. To remove it, access the admin panel.
        </p>
    </body>
    </html>
    ";
    
    $headers = "From: website@mbewegroup.co.za\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    mail($to, $subject, $message, $headers);

    // Return latest testimonials
    $result = $conn->query("SELECT name, email, testimonial FROM testimonials ORDER BY created_at DESC LIMIT 4");
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($conn)) $conn->close();
}
?>