<?php
require_once '../config.php';
require_once '../includes/functions.php';
session_start();

$pageTitle = 'Email Não Verificado - ' . APP_NAME;
$showHeader = false;
$showFooter = true;

// Check if unverified email exists in session
$email = $_SESSION['unverified_email'] ?? '';

if (empty($email)) {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card auth-card">
                <div class="card-header text-center">
                    <img src="../img/logo1.png" alt="<?php echo APP_NAME; ?>" class="logo">
                    <h4>Email Não Verificado</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Confirmação Necessária</strong>
                        <p>Precisa de confirmar o seu email <strong><?php echo htmlspecialchars($email); ?></strong> antes de iniciar sessão.</p>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-envelope"></i>
                        <strong>Verifique o seu email</strong>
                        <p>Procure por um email de confirmação na sua caixa de entrada e clique no link para confirmar a sua conta.</p>
                        <p><small>Não se esqueça de verificar a pasta de spam/lixo.</small></p>
                    </div>
                    
                    <div class="text-center">
                        <button type="button" class="btn btn-warning" 
                                data-action="resend-confirmation" 
                                data-email="<?php echo htmlspecialchars($email); ?>">
                            <i class="fas fa-paper-plane"></i> Reenviar Email de Confirmação
                        </button>
                        <button type="button" class="btn btn-info" 
                                data-action="check-verification" 
                                data-email="<?php echo htmlspecialchars($email); ?>">
                            <i class="fas fa-check-circle"></i> Verificar Status
                        </button>
                        <a href="../login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Voltar ao Login
                        </a>
                    </div>
                    
                    <hr>
                    
                    <div class="text-center">
                        <p class="text-muted">
                            <small>Problemas com a confirmação? <a href="mailto:admin@emotiondanceacademy.com">Contacte-nos</a></small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
