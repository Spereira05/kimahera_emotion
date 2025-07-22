<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';

// Test login helper - for debugging only
// This file should be removed in production

echo "<h2>Test Login Helper</h2>";
echo "<p><strong>Default Admin Credentials:</strong></p>";
echo "<ul>";
echo "<li>Email: admin@emotiondanceacademy.com</li>";
echo "<li>Password: 123456</li>";
echo "</ul>";

// Check current login status
echo "<h3>Current Status:</h3>";
if (isLoggedIn()) {
    echo "<p style='color: green;'>✅ User is logged in</p>";
    echo "<p>User ID: " . ($_SESSION['usuario_id'] ?? 'Not set') . "</p>";
    echo "<p>User Name: " . ($_SESSION['usuario_nome'] ?? 'Not set') . "</p>";
    echo "<p>User Email: " . ($_SESSION['usuario_email'] ?? 'Not set') . "</p>";
    echo "<br>";
    echo "<a href='auth/logout.php' style='background: #dc3545; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>Test Logout</a>";
    echo " | ";
    echo "<a href='index.php' style='background: #007bff; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>Go to Dashboard</a>";
} else {
    echo "<p style='color: red;'>❌ User is not logged in</p>";
    echo "<br>";
    echo "<a href='login.php' style='background: #28a745; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>Go to Login</a>";
}

// Check if admin user exists
echo "<h3>Database Check:</h3>";
try {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT id, nome, email, email_verificado, ativo FROM usuarios WHERE email = 'admin@emotiondanceacademy.com'");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        echo "<p style='color: green;'>✅ Admin user exists in database</p>";
        echo "<p>ID: " . $admin['id'] . "</p>";
        echo "<p>Name: " . $admin['nome'] . "</p>";
        echo "<p>Email: " . $admin['email'] . "</p>";
        echo "<p>Email Verified: " . ($admin['email_verificado'] ? 'Yes' : 'No') . "</p>";
        echo "<p>Active: " . ($admin['ativo'] ? 'Yes' : 'No') . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Admin user not found in database</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Quick login button (for testing only)
if (!isLoggedIn()) {
    echo "<h3>Quick Test Login:</h3>";
    echo "<form method='POST' action='auth/login.php' style='background: #f8f9fa; padding: 20px; border-radius: 5px; max-width: 300px;'>";
    echo "<input type='email' name='email' value='admin@emotiondanceacademy.com' style='width: 100%; padding: 8px; margin-bottom: 10px;' readonly>";
    echo "<input type='password' name='senha' value='123456' style='width: 100%; padding: 8px; margin-bottom: 10px;' readonly>";
    echo "<button type='submit' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Test Login</button>";
    echo "</form>";
}

echo "<hr>";
echo "<h3>Authentication Files Status:</h3>";
$authFiles = ['auth/login.php', 'auth/logout.php', 'auth/forgot-password.php', 'auth/reset-password.php'];
foreach ($authFiles as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file exists</p>";
    } else {
        echo "<p style='color: red;'>❌ $file missing</p>";
    }
}

echo "<hr>";
echo "<p><em>Remember to delete this test file in production!</em></p>";
?>
