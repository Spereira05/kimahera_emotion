<?php
// Helper functions for the application

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Redirect with message
 */
function redirectWithMessage($location, $message, $type = 'erro') {
    $_SESSION[$type] = $message;
    header("Location: $location");
    exit();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['logado']) && $_SESSION['logado'] === true;
}

/**
 * Require login (redirect if not logged in)
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirectWithMessage('../index.php', 'Precisa de iniciar sessão para aceder a esta página.');
    }
}

/**
 * Get user avatar (placeholder function)
 */
function getUserAvatar($userId) {
    return "https://ui-avatars.com/api/?name=" . urlencode($_SESSION['usuario_nome']) . "&background=e91e63&color=fff&size=40";
}

/**
 * Generate password reset token
 */
function generatePasswordResetToken($email) {
    global $mysqli;
    
    // Delete old tokens for this email
    $stmt = $mysqli->prepare("DELETE FROM password_resets WHERE email = ? OR expires_at < NOW()");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    
    // Generate new token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Insert new token
    $stmt = $mysqli->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $token, $expires);
    
    if ($stmt->execute()) {
        return $token;
    }
    return false;
}

/**
 * Verify password reset token
 */
function verifyPasswordResetToken($token) {
    global $mysqli;
    
    $stmt = $mysqli->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        return $result->fetch_assoc()['email'];
    }
    return false;
}

/**
 * Mark reset token as used
 */
function markTokenAsUsed($token) {
    global $mysqli;
    
    $stmt = $mysqli->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
    $stmt->bind_param("s", $token);
    return $stmt->execute();
}

/**
 * Send password reset email
 */
function sendPasswordResetEmail($email, $token, $userName) {
    $resetLink = BASE_URL . "auth/reset-password.php?token=" . $token;
    
    // Preparar dados para envio de email
    $_SESSION['reset_email_data'] = [
        'email' => $email,
        'token' => $token,
        'user_name' => $userName,
        'reset_link' => $resetLink,
        'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        'app_name' => APP_NAME,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    return true;
}

/**
 * Get password strength
 */
function getPasswordStrength($password) {
    $strength = 0;
    $feedback = [];
    
    if (strlen($password) >= 8) {
        $strength++;
    } else {
        $feedback[] = "Pelo menos 8 caracteres";
    }
    
    if (preg_match('/[A-Z]/', $password)) {
        $strength++;
    } else {
        $feedback[] = "Uma letra maiúscula";
    }
    
    if (preg_match('/[a-z]/', $password)) {
        $strength++;
    } else {
        $feedback[] = "Uma letra minúscula";
    }
    
    if (preg_match('/[0-9]/', $password)) {
        $strength++;
    } else {
        $feedback[] = "Um número";
    }
    
    if (preg_match('/[^A-Za-z0-9]/', $password)) {
        $strength++;
    } else {
        $feedback[] = "Um caractere especial";
    }
    
    return [
        'strength' => $strength,
        'feedback' => $feedback,
        'level' => ['Muito fraca', 'Fraca', 'Média', 'Boa', 'Muito forte'][$strength]
    ];
}

/**
 * Log user activity
 */
function logActivity($userId, $action, $details = '') {
    global $mysqli;
    
    // Verificar se a tabela existe antes de tentar inserir
    $tableCheck = $mysqli->query("SHOW TABLES LIKE 'user_activity'");
    if ($tableCheck->num_rows == 0) {
        return false; // Tabela não existe
    }
    
    // Se userId é email, tentar obter o ID real do utilizador
    if (is_string($userId) && filter_var($userId, FILTER_VALIDATE_EMAIL)) {
        $email = $userId;
        $stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $userId = $user['id'];
        } else {
            // Utilizador não encontrado, não fazer log com constraint
            return false;
        }
    }
      // Verificar se o user_id existe na tabela usuarios (exceto para user_id = 0 que é para logs de segurança)
    if (is_numeric($userId) && $userId > 0) {
        $stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            // User_id não existe, não fazer log
            return false;
        }
    }
    
    try {
        // Para user_id = 0, inserir diretamente (logs de segurança)
        // Para outros user_ids, usar o valor fornecido (já validado acima)
        $stmt = $mysqli->prepare("INSERT INTO user_activity (user_id, action, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        // Garantir que user_id é um inteiro
        $userId = (int)$userId;
        
        $stmt->bind_param("issss", $userId, $action, $details, $ip, $userAgent);
        return $stmt->execute();
    } catch (Exception $e) {
        // Se falhar, não quebrar o sistema
        error_log("Log activity failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate email confirmation token
 */
function generateEmailConfirmationToken($email) {
    global $mysqli;
    
    // Generate secure token
    $token = bin2hex(random_bytes(32));
    
    // Token expires in 24 hours
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Invalidate any existing tokens for this email
    $stmt = $mysqli->prepare("UPDATE email_confirmations SET used = 1 WHERE email = ? AND used = 0");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    
    // Insert new token
    $stmt = $mysqli->prepare("INSERT INTO email_confirmations (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $token, $expires);
    
    if ($stmt->execute()) {
        return $token;
    }
    
    return false;
}

/**
 * Generate and update user verification token
 */
function generateUserVerificationToken($userId) {
    global $mysqli;
    
    // Generate secure token
    $token = bin2hex(random_bytes(32));
    
    // Token expires in 24 hours
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Update user record with verification token
    $stmt = $mysqli->prepare("UPDATE usuarios SET token_verificacao = ?, token_verificacao_expira = ? WHERE id = ?");
    $stmt->bind_param("ssi", $token, $expires, $userId);
    
    if ($stmt->execute()) {
        return $token;
    }
    
    return false;
}

/**
 * Verify email confirmation token
 */
function verifyEmailConfirmationToken($token) {
    global $mysqli;
    
    // Check if token is valid and not expired
    $stmt = $mysqli->prepare("SELECT email FROM email_confirmations WHERE token = ? AND used = 0 AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $email = $row['email'];
        
        // Mark token as used
        $stmt = $mysqli->prepare("UPDATE email_confirmations SET used = 1 WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        
        // Mark user as verified
        $stmt = $mysqli->prepare("UPDATE usuarios SET email_verificado = 1, token_verificacao = NULL, token_verificacao_expira = NULL WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        return $email;
    }
    
    return false;
}

/**
 * Verify user token (from usuarios table)
 */
function verifyUserToken($token) {
    global $mysqli;
    
    // Check if token is valid and not expired in email_confirmations table
    $stmt = $mysqli->prepare("SELECT nome, email, senha_hash FROM email_confirmations WHERE token = ? AND expires_at > NOW() AND used = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Check if user already exists in usuarios table
        $stmt_check = $mysqli->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt_check->bind_param("s", $row['email']);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            // User already exists, just mark token as used
            $stmt_update = $mysqli->prepare("UPDATE email_confirmations SET used = 1 WHERE token = ?");
            $stmt_update->bind_param("s", $token);
            $stmt_update->execute();
            
            return $row['email'];
        }
        
        // Create user in usuarios table
        $stmt_insert = $mysqli->prepare("INSERT INTO usuarios (nome, email, senha, email_verificado) VALUES (?, ?, ?, 1)");
        $stmt_insert->bind_param("sss", $row['nome'], $row['email'], $row['senha_hash']);
        
        if ($stmt_insert->execute()) {
            $newUserId = $mysqli->insert_id;
            
            // Mark token as used
            $stmt_update = $mysqli->prepare("UPDATE email_confirmations SET used = 1 WHERE token = ?");
            $stmt_update->bind_param("s", $token);
            $stmt_update->execute();
            
            // Log successful registration
            logActivity($newUserId, 'user_registered', 'New user account created after email confirmation: ' . $row['email']);
            
            return $row['email'];
        }
    }
    
    return false;
}

/**
 * Check if user email is verified
 */
function isEmailVerified($email) {
    global $mysqli;
    
    $stmt = $mysqli->prepare("SELECT email_verificado FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['email_verificado'] == 1;
    }
    
    return false;
}

/**
 * Resend email confirmation
 */
function resendEmailConfirmation($email) {
    global $mysqli;
    
    // Check if user exists and is not verified
    $stmt = $mysqli->prepare("SELECT id, nome FROM usuarios WHERE email = ? AND email_verificado = 0");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Generate new token
        $token = generateUserVerificationToken($user['id']);
        
        if ($token) {
            return [
                'success' => true,
                'token' => $token,
                'nome' => $user['nome']
            ];
        }
    }
    
    return ['success' => false];
}

/**
 * Check email confirmation status
 */
function checkEmailConfirmationStatus($email) {
    global $mysqli;
    
    // Check if user exists in usuarios table (confirmed)
    $stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return ['status' => 'confirmed', 'message' => 'Email confirmado com sucesso!'];
    }
    
    // Check if confirmation is pending in email_confirmations table
    $stmt = $mysqli->prepare("SELECT token, expires_at FROM email_confirmations WHERE email = ? AND used = 0");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Check if token is still valid
        if (strtotime($row['expires_at']) > time()) {
            return ['status' => 'pending', 'message' => 'Email ainda não confirmado. Verifique o seu email.'];
        } else {
            return ['status' => 'expired', 'message' => 'O link de confirmação expirou. Solicite um novo.'];
        }
    }
    
    return ['status' => 'not_found', 'message' => 'Nenhuma confirmação pendente encontrada.'];
}
?>