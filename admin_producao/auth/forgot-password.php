<?php
session_start();
require_once "../config.php";
require_once "../includes/functions.php";

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: ../index.php");
    exit();
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = sanitizeInput($_POST["email"] ?? "");

    // Validate input
    if (empty($email)) {
        redirectWithMessage(
            "../pages/forgot-password.php",
            "Por favor, insira o seu email.",
            "erro"
        );
    }

    if (!validateEmail($email)) {
        redirectWithMessage(
            "../pages/forgot-password.php",
            "Por favor, insira um email válido.",
            "erro"
        );
    }

    try {
        global $mysqli;

        // Check if user exists
        $stmt = $mysqli->prepare("SELECT id, nome, email FROM usuarios WHERE email = ? AND ativo = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Generate password reset token
            $token = generatePasswordResetToken($email);

            if ($token) {
                // Send password reset email
                if (sendPasswordResetEmail($email, $token, $user["nome"])) {
                    // Log the password reset request
                    logActivity($user["id"], "password_reset_requested", "Password reset requested for email: " . $email);

                    redirectWithMessage(
                        "../pages/forgot-password.php",
                        "Se o email existir na nossa base de dados, receberá instruções para redefinir a sua palavra-passe.",
                        "sucesso"
                    );
                } else {
                    // Email sending failed
                    logActivity($user["id"], "password_reset_email_failed", "Failed to send password reset email to: " . $email);
                    redirectWithMessage(
                        "../pages/forgot-password.php",
                        "Erro ao enviar email. Tente novamente mais tarde.",
                        "erro"
                    );
                }
            } else {
                // Token generation failed
                logActivity($user["id"], "password_reset_token_failed", "Failed to generate password reset token for: " . $email);
                redirectWithMessage(
                    "../pages/forgot-password.php",
                    "Erro interno. Tente novamente mais tarde.",
                    "erro"
                );
            }
        } else {
            // User doesn't exist - but don't reveal this for security
            logActivity(0, "password_reset_attempt_nonexistent", "Password reset attempt for non-existent email: " . $email);
            redirectWithMessage(
                "../pages/forgot-password.php",
                "Se o email existir na nossa base de dados, receberá instruções para redefinir a sua palavra-passe.",
                "sucesso"
            );
        }
    } catch (Exception $e) {
        // Database error
        error_log("Forgot password error: " . $e->getMessage());
        logActivity(0, "password_reset_error", "Database error during password reset: " . $e->getMessage());
        redirectWithMessage(
            "../pages/forgot-password.php",
            "Erro interno. Tente novamente mais tarde.",
            "erro"
        );
    }
} else {
    // Direct access to forgot password handler - redirect to forgot password page
    header("Location: ../pages/forgot-password.php");
    exit();
}
?>
