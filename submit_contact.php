<?php
header('Content-Type: application/json');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'mbewereg_developer');
define('DB_PASS', 'devM@2024!!');
define('DB_NAME', 'mbewereg_lawdatabase');
define('ADMIN_EMAILS', ['admin@mbewegroup.co.za', 'athisiah@mbewegroup.co.za']);
define('FROM_EMAIL', 'website@mbewegroup.co.za');

try {
    // Validate inputs
    $required = ['name', 'email', 'message'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Please fill in all required fields");
        }
    }

    // Sanitize data
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars(trim($_POST['message']));
    $ip = $_SERVER['REMOTE_ADDR'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Please enter a valid email address");
    }

    // Database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }

    // Insert record
    $stmt = $conn->prepare("INSERT INTO drop_us_a_line (name, email, message, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $message, $ip);
    if (!$stmt->execute()) {
        throw new Exception("Failed to save your message");
    }

    // Send emails
    $subject = "New Contact Form Submission from $name";
    $html_message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { color: #004aad; padding-bottom: 10px; border-bottom: 1px solid #eee; }
            .content { padding: 15px 0; }
            .footer { margin-top: 20px; font-size: 0.9em; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>New Website Message</h2>
            </div>
            <div class='content'>
                <p><strong>From:</strong> $name &lt;$email&gt;</p>
                <p><strong>IP Address:</strong> $ip</p>
                <p><strong>Message:</strong></p>
                <div style='background:#f8f9fa;padding:15px;border-radius:5px;margin-top:10px;'>
                    ".nl2br($message)."
                </div>
            </div>
            <div class='footer'>
                <p>Received at: ".date('Y-m-d H:i:s')."</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: '.FROM_EMAIL,
        'Reply-To: '.$email,
        'X-Mailer: PHP/'.phpversion()
    ];

    // Send to all recipients
    $email_sent = true;
    foreach (ADMIN_EMAILS as $to) {
        if (!mail($to, $subject, $html_message, implode("\r\n", $headers))) {
            $email_sent = false;
            error_log("Failed to send email to: $to");
        }
    }

    if (!$email_sent) {
        error_log("Email sending partially failed, but message was saved to database");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your message has been sent successfully.'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) $conn->close();
}
?>