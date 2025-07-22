<?php
require_once '../config.php';
require_once '../includes/functions.php';
session_start();

$pageTitle = 'Confirmar Email - ' . APP_NAME;
$showHeader = false;
$showFooter = true;

// Handle token verification
$token = $_GET['token'] ?? '';
$verified = false;
$email = '';
$error = '';

if (!empty($token)) {
    $email = verifyUserToken($token);
    
    if ($email) {
        $verified = true;
        logActivity(0, 'email_confirmed', 'Email confirmed: ' . $email);
    } else {
        $error = 'Token inválido ou expirado.';
    }
}

require_once '../includes/header.php';
?>

<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card" style="max-width: 500px;">
            <div class="auth-header">
                <img src="<?php echo BASE_URL; ?>img/logo1.png" alt="Logo" class="mb-3 mt-3" style="max-height: 60px;">
                <h3 style="margin-bottom: 0.2rem;">Confirmação de Email</h3>
                <p class="mb-0">Verificação da sua conta</p>
            </div>
            <div class="auth-body-content">
                <?php if ($verified): ?>
                    <div class="alert alert-success mb-3">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Email confirmado com sucesso!</strong>
                        <p class="mb-2 mt-2">O seu email <strong><?php echo htmlspecialchars($email); ?></strong> foi confirmado.</p>
                        <p class="mb-0">A sua conta foi criada e pode agora iniciar sessão normalmente.</p>
                    </div>
                    
                    <div class="text-center">
                        <a href="../login.php" class="btn btn-auth">
                            <i class="fas fa-sign-in-alt me-1"></i>Iniciar Sessão
                        </a>
                    </div>
                <?php elseif (!empty($error)): ?>
                    <div class="alert alert-danger mb-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Erro!</strong>
                        <p class="mb-0 mt-2"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                    
                    <div class="text-center">
                        <div class="d-grid gap-2">
                            <a href="../pages/resend-confirmation.php" class="btn btn-outline-secondary">
                                <i class="fas fa-paper-plane me-1"></i>Reenviar Confirmação
                            </a>
                            <a href="../login.php" class="btn btn-auth">
                                <i class="fas fa-sign-in-alt me-1"></i>Voltar ao Login
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Token não fornecido</strong>
                        <p class="mb-0 mt-2">Para confirmar o seu email, clique no link enviado para o seu endereço de email.</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="d-grid gap-2">
                            <a href="../pages/resend-confirmation.php" class="btn btn-outline-secondary">
                                <i class="fas fa-paper-plane me-1"></i>Reenviar Confirmação
                            </a>
                            <a href="../login.php" class="btn btn-auth">
                                <i class="fas fa-sign-in-alt me-1"></i>Voltar ao Login
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php require_once '../includes/footer.php'; ?>
