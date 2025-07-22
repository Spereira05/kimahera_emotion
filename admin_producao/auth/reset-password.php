<?php
session_start();
require_once "../config.php";
require_once "../includes/functions.php";

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: ../index.php");
    exit();
}

// Check if token is provided
$token = $_GET["token"] ?? "";

if (empty($token)) {
    redirectWithMessage(
        "../pages/forgot-password.php",
        "Token de recuperação inválido.",
        "erro"
    );
}

// Verify token
$email = verifyPasswordResetToken($token);
if (!$email) {
    redirectWithMessage(
        "../pages/forgot-password.php",
        "Token de recuperação inválido ou expirado.",
        "erro"
    );
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newPassword = $_POST["senha"] ?? "";
    $confirmPassword = $_POST["confirmar_senha"] ?? "";

    // Validate input
    if (empty($newPassword) || empty($confirmPassword)) {
        redirectWithMessage(
            "../pages/reset-password.php?token=" . urlencode($token),
            "Por favor, preencha todos os campos.",
            "erro"
        );
    }

    if ($newPassword !== $confirmPassword) {
        redirectWithMessage(
            "../pages/reset-password.php?token=" . urlencode($token),
            "As palavras-passe não coincidem.",
            "erro"
        );
    }

    if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
        redirectWithMessage(
            "../pages/reset-password.php?token=" . urlencode($token),
            "A palavra-passe deve ter pelo menos " . PASSWORD_MIN_LENGTH . " caracteres.",
            "erro"
        );
    }

    try {
        global $mysqli;

        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update user password
        $stmt = $mysqli->prepare("UPDATE usuarios SET senha = ?, last_password_reset = NOW() WHERE email = ?");
        $stmt->bind_param("ss", $hashedPassword, $email);

        if ($stmt->execute()) {
            // Mark token as used
            markTokenAsUsed($token);

            // Get user ID for logging
            $stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                logActivity($user["id"], "password_reset_completed", "Password successfully reset for email: " . $email);
            }

            redirectWithMessage(
                "../login.php",
                "Palavra-passe redefinida com sucesso. Pode agora iniciar sessão.",
                "sucesso"
            );
        } else {
            logActivity(0, "password_reset_failed", "Failed to update password for email: " . $email);
            redirectWithMessage(
                "../pages/reset-password.php?token=" . urlencode($token),
                "Erro ao redefinir palavra-passe. Tente novamente.",
                "erro"
            );
        }
    } catch (Exception $e) {
        // Database error
        error_log("Reset password error: " . $e->getMessage());
        logActivity(0, "password_reset_error", "Database error during password reset: " . $e->getMessage());
        redirectWithMessage(
            "../pages/reset-password.php?token=" . urlencode($token),
            "Erro interno. Tente novamente mais tarde.",
            "erro"
        );
    }
}

// If we reach here, show the reset password form
$pageTitle = 'Redefinir Senha';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/auth.css" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <img src="<?php echo BASE_URL; ?>img/logo1.png" alt="Logo" class="mb-3 mt-3" style="max-height: 60px;">
                <h3 style="margin-bottom: 0.2rem;">Redefinir Palavra-passe</h3>
                <p class="mb-0">Introduza a sua nova palavra-passe</p>
            </div>
            <div class="auth-body-content">
                <?php include '../includes/auth-alerts.php'; ?>

                <form action="reset-password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="senha" class="form-label">
                            <i class="fas fa-lock me-1"></i>Nova Palavra-passe
                        </label>
                        <input type="password" class="form-control" id="senha" name="senha" required
                               minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" autocomplete="new-password">
                        <div class="invalid-feedback">
                            A palavra-passe deve ter pelo menos <?php echo PASSWORD_MIN_LENGTH; ?> caracteres.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="confirmar_senha" class="form-label">
                            <i class="fas fa-lock me-1"></i>Confirmar Palavra-passe
                        </label>
                        <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required
                               minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" autocomplete="new-password">
                        <div class="invalid-feedback">
                            Por favor, confirme a sua palavra-passe.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-auth w-100 mb-3">
                        <i class="fas fa-save me-1"></i>Redefinir Palavra-passe
                    </button>
                </form>

                <div class="text-center">
                    <a href="../login.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i>Voltar ao Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirmar_senha').addEventListener('input', function() {
            const password = document.getElementById('senha').value;
            const confirmPassword = this.value;

            if (password !== confirmPassword) {
                this.setCustomValidity('As palavras-passe não coincidem');
            } else {
                this.setCustomValidity('');
            }
        });

        // Bootstrap form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>
</html>
