<?php

// Prevent multiple inclusions
if (defined('CONFIG_LOADED')) {
    return;
}
define('CONFIG_LOADED', true);

// Suppress PHP deprecated warnings and notices
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);

// ========================================
// APPLICATION CONFIGURATION
// ========================================

// Application constants
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Emotion Dance Academy');
    //define('BASE_URL', 'http://localhost/admin/');
    define('BASE_URL', '');
    define('PASSWORD_MIN_LENGTH', 6);
}

// ========================================
// DATABASE CONFIGURATION
// ========================================

$db_host = '127.0.0.1';
$db_user = 'root';
$db_password = '';
$db_db = 'admin_emotiondanceacademy';
$db_port = 8889;

// Create single mysqli instance to avoid duplicates
if (!isset($mysqli)) {
    $mysqli = new mysqli($db_host, $db_user, $db_password, $db_db);
    $mysqli->set_charset("utf8");
    $mysqli->autocommit(true); // Ensure immediate visibility of changes
    
    // Set isolation level to READ COMMITTED for immediate visibility across connections
    $mysqli->query("SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");
    
    if($mysqli->connect_error) {
        die('Database connection failed: ' . $mysqli->connect_error);
    }
}

// ========================================
// EMAIL CONFIGURATION
// ========================================

// Email service public key
if (!defined('EMAILJS_PUBLIC_KEY')) {
    define('EMAILJS_PUBLIC_KEY', 'LcKaVqblL08aOind7');
    
    // Email service ID
    define('EMAILJS_SERVICE_ID', 'service_4smcjke');
    
    // Password reset template ID
    define('EMAILJS_TEMPLATE_PASSWORD_RESET', 'template_xax7iig');
    
    // Email confirmation template ID
    define('EMAILJS_TEMPLATE_EMAIL_CONFIRMATION', 'template_mncejlb');
    
    // Ativar sistema de envio de emails
    define('EMAILJS_ENABLED', true);
}

/**
 * Verificar se sistema de email está configurado
 */
if (!function_exists('isEmailJSConfigured')) {
    function isEmailJSConfigured() {
        return EMAILJS_ENABLED && 
               EMAILJS_PUBLIC_KEY !== 'YOUR_PUBLIC_KEY_HERE' &&
               !empty(EMAILJS_PUBLIC_KEY) &&
               !empty(EMAILJS_SERVICE_ID) &&
               !empty(EMAILJS_TEMPLATE_PASSWORD_RESET) &&
               !empty(EMAILJS_TEMPLATE_EMAIL_CONFIRMATION);
    }
}

/**
 * Get email configuration for frontend
 */
if (!function_exists('getEmailJSConfig')) {
    function getEmailJSConfig() {
        return [
            'enabled' => isEmailJSConfigured(),
            'public_key' => EMAILJS_PUBLIC_KEY,
            'service_id' => EMAILJS_SERVICE_ID,
            'template_id' => EMAILJS_TEMPLATE_PASSWORD_RESET,
            'confirmation_template_id' => EMAILJS_TEMPLATE_EMAIL_CONFIRMATION
        ];
    }
}

// ========================================
// SESSION CONFIGURATION
// ========================================

// Timezone
date_default_timezone_set('Europe/Lisbon');

// Session configuration (must be set before session_start())
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
}

// ========================================
// AUTO-CREATE AUTHENTICATION TABLES
// ========================================

// Create authentication tables automatically if they don't exist
$tables_sql = [
    // Users table
    "CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        email VARCHAR(150) UNIQUE NOT NULL,
        senha VARCHAR(255) NOT NULL,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ultimo_login TIMESTAMP NULL,
        last_password_reset TIMESTAMP NULL,
        ativo TINYINT(1) DEFAULT 1,
        email_verificado TINYINT(1) DEFAULT 0,
        token_verificacao VARCHAR(255) NULL,
        token_verificacao_expira TIMESTAMP NULL
    )",
    
    // Password resets table
    "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(150) NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_token (token),
        INDEX idx_expires (expires_at)
    )",
    
    // Email confirmations table
    "CREATE TABLE IF NOT EXISTS email_confirmations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL,
        senha_hash VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_token (token),
        INDEX idx_expires (expires_at)
    )",
    
    // User activity table (optional)
    "CREATE TABLE IF NOT EXISTS user_activity (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_action (action),
        INDEX idx_created_at (created_at)
    )"
];

// Execute table creation
foreach ($tables_sql as $sql) {
    $mysqli->query($sql);
}

// Insert default admin user if not exists
$checkAdmin = $mysqli->query("SELECT COUNT(*) as count FROM usuarios WHERE email = 'admin@emotiondanceacademy.com'");
if ($checkAdmin->fetch_assoc()['count'] == 0) {
    $adminPassword = password_hash('123456', PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare("INSERT INTO usuarios (nome, email, senha, email_verificado) VALUES (?, ?, ?, 1)");
    $nome = 'Administrador';
    $email = 'admin@emotiondanceacademy.com';
    $stmt->bind_param("sss", $nome, $email, $adminPassword);
    $stmt->execute();
}

?>