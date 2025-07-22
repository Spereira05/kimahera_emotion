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
    $senha = $_POST["senha"] ?? "";

    // Validate input
    if (empty($email) || empty($senha)) {
        redirectWithMessage(
            "../login.php",
            "Por favor, preencha todos os campos.",
            "erro",
        );
    }

    if (!validateEmail($email)) {
        redirectWithMessage(
            "../login.php",
            "Por favor, insira um email válido.",
            "erro",
        );
    }

    try {
        global $mysqli;

        // Check if user exists and get user data
        $stmt = $mysqli->prepare(
            "SELECT id, nome, email, senha, email_verificado, ativo FROM usuarios WHERE email = ?",
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Check if account is active
            if (!$user["ativo"]) {
                logActivity(
                    0,
                    "login_attempt_inactive",
                    "Login attempt with inactive account: " . $email,
                );
                redirectWithMessage(
                    "../login.php",
                    "Conta inativa. Contacte o administrador.",
                    "erro",
                );
            }

            // Check if email is verified (if email verification is enabled)
            if (!$user["email_verificado"]) {
                // Store unverified email in session for potential resend
                $_SESSION["unverified_email"] = $email;
                logActivity(
                    0,
                    "login_attempt_unverified",
                    "Login attempt with unverified email: " . $email,
                );
                redirectWithMessage(
                    "../pages/email-not-verified.php",
                    "Email ainda não verificado. Verifique o seu email.",
                    "info",
                );
            }

            // Verify password
            if (password_verify($senha, $user["senha"])) {
                // Login successful - create session
                $_SESSION["logado"] = true;
                $_SESSION["usuario_id"] = $user["id"];
                $_SESSION["usuario_nome"] = $user["nome"];
                $_SESSION["usuario_email"] = $user["email"];

                // Log successful login
                logActivity(
                    $user["id"],
                    "user_login",
                    "User logged in successfully",
                );

                // Redirect to dashboard
                redirectWithMessage(
                    "../index.php",
                    "Login realizado com sucesso!",
                    "sucesso",
                );
            } else {
                // Invalid password
                logActivity(
                    0,
                    "login_attempt_failed",
                    "Failed login attempt for email: " . $email,
                );
                redirectWithMessage(
                    "../login.php",
                    "Email ou palavra-passe incorretos.",
                    "erro",
                );
            }
        } else {
            // User doesn't exist
            logActivity(
                0,
                "login_attempt_nonexistent",
                "Login attempt with non-existent email: " . $email,
            );
            redirectWithMessage(
                "../login.php",
                "Email ou palavra-passe incorretos.",
                "erro",
            );
        }
    } catch (Exception $e) {
        // Database error
        error_log("Login error: " . $e->getMessage());
        logActivity(
            0,
            "login_error",
            "Database error during login: " . $e->getMessage(),
        );
        redirectWithMessage(
            "../login.php",
            "Erro interno. Tente novamente mais tarde.",
            "erro",
        );
    }
} else {
    // Direct access to login handler - redirect to login page
    header("Location: ../login.php");
    exit();
}
?>
