<?php
require_once __DIR__ . '/../includes/mailer.php';

send_app_mail(
    'test@example.com',
    'Test User',
    'Mail Log Test',
    '<p>This is a test mail body.</p>'
);

echo "Mail function executed.";
