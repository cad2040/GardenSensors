<?php
// Database settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'SoilSensors');
define('DB_USER', 'SoilSensors');
define('DB_PASS', 'SoilSensors123');

// FTP settings
define('FTP_HOST', 'your_ftp_host');
define('FTP_USER', 'your_ftp_user');
define('FTP_PASS', 'your_ftp_pass');
define('FTP_PATH', '/public_html/plots/');

// Email settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_app_password');
define('ALERT_EMAIL', 'alerts@yourdomain.com');

// Application settings
define('DATA_RETENTION_DAYS', 30);
define('ALERT_THRESHOLD', 20);
define('REFRESH_INTERVAL', 300);
