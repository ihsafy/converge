<?php
require_once __DIR__ . '/../includes/mailer.php';

send_app_mail(
    'test@example.com',
    'Test User',
    'Test Email',
    '<p>This is a test email log.</p>'
);

echo "Mail function executed. Check mail_logs folder.";
