<?php
require_once __DIR__ . '/../config/config.php';

/**
 * INTERNAL MAIL LOGGER (TXT FILE)
 * Logs every email attempt (success or fail)
 */
function log_mail_txt($to, $toName, $subject, $body, $status) {

    $logDir = __DIR__ . '/../mail_logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $filename = $logDir . '/mail_' . date('Y-m-d') . '.txt';

    $entry  = "--------------------------------------\n";
    $entry .= "Date    : " . date('Y-m-d H:i:s') . "\n";
    $entry .= "To      : $to ($toName)\n";
    $entry .= "Subject : $subject\n";
    $entry .= "Status  : $status\n";
    $entry .= "Message :\n";
    $entry .= strip_tags($body) . "\n";
    $entry .= "--------------------------------------\n\n";

    file_put_contents($filename, $entry, FILE_APPEND);
}

/**
 * GENERIC MAILER FUNCTION
 * Wraps content in a nice HTML template and sends it.
 */
function send_app_mail(string $to, string $toName, string $subject, string $body): bool {
    
    // 1. Construct the HTML Template
    $htmlContent = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f3f4f6; padding: 20px; color: #333; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
            .header { border-bottom: 2px solid #2563eb; padding-bottom: 15px; margin-bottom: 20px; }
            .header h2 { margin: 0; color: #1e293b; }
            .content { line-height: 1.6; font-size: 16px; color: #4b5563; }
            .footer { margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 15px; font-size: 12px; color: #9ca3af; text-align: center; }
            .btn { display: inline-block; background: #2563eb; color: #ffffff !important; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 10px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>CONVERGE Club System</h2>
            </div>
            <div class='content'>
                <p>Hello <strong>$toName</strong>,</p>
                $body
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " CONVERGE. All rights reserved.</p>
                <p>This is an automated message. Please do not reply.</p>
            </div>
        </div>
    </body>
    </html>";

    // 2. Set Headers
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: CONVERGE Admin <no-reply@converge.com>\r\n";

    // 3. Send Email (localhost safe)
    $sent = @mail($to, $subject, $htmlContent, $headers);

    // 4. TXT LOG (ALWAYS)
    log_mail_txt(
        $to,
        $toName,
        $subject,
        $body,
        $sent ? 'SENT' : 'FAILED'
    );

    return $sent;
}

/**
 * SPECIFIC WRAPPER FOR MEMBER WELCOME
 * Used by admin_members.php & CSV import
 */
function send_welcome_email($toEmail, $userName, $password) {

    $loginUrl = "http://localhost/converge/public/login.php"; 

    $subject = "Welcome to CONVERGE! Registration Successful";
    
    $body = "
        <p>You have been successfully registered as a member.</p>
        <div style='background: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; margin: 20px 0;'>
            <p><strong>Login Credentials:</strong></p>
            <p>📧 Email: <strong>$toEmail</strong></p>
            <p>🔑 Password: <code>$password</code></p>
        </div>
        <p>Please log in and change your password.</p>
        <p><a href='$loginUrl' class='btn'>Login to Dashboard</a></p>
    ";

    return send_app_mail($toEmail, $userName, $subject, $body);
}
?>
