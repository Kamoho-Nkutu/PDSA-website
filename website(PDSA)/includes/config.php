<?php
// PDSA Veterinary Clinic - Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'pdsa_admin');
define('DB_PASS', 'secure_password_123');
define('DB_NAME', 'pdsa_clinic');

// Stripe API Configuration
define('STRIPE_SECRET_KEY', 'sk_test_your_stripe_secret_key');
define('STRIPE_PUBLIC_KEY', 'pk_test_your_stripe_public_key');

// Email Configuration
define('SMTP_HOST', 'smtp.yourdomain.com');
define('SMTP_USER', 'noreply@yourdomain.com');
define('SMTP_PASS', 'email_password');
define('SMTP_PORT', 587);
define('FROM_EMAIL', 'noreply@yourdomain.com');
define('FROM_NAME', 'PDSA Veterinary Clinic');

// Security Settings
define('CSRF_TOKEN_SECRET', 'your_csrf_token_secret_123');
define('PASSWORD_COST', 12); // bcrypt cost factor

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Europe/London');

// Start session with secure settings
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// Include necessary files
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Appointment.php';
require_once __DIR__ . '/../classes/Pet.php';
require_once __DIR__ . '/../classes/Payment.php';
require_once __DIR__ . '/functions.php';

// Initialize database connection
try {
    $db = new Database();
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("We're experiencing technical difficulties. Please try again later.");
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>