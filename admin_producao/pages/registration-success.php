<?php
require_once '../config.php';
require_once '../includes/functions.php';
session_start();

$pageTitle = 'Registo Concluído - ' . APP_NAME;
$showHeader = false;
$showFooter = true;

// Check if registration data exists in session
if (!isset($_SESSION['registration_data'])) {
    header('Location: ../pages/register.php');
    exit();
}

$registrationData = $_SESSION['registration_data'];
$email = $registrationData['email'];
$nome = $registrationData['nome'];
$token = $registrationData['token'];

// Clear registration data from session
unset($_SESSION['registration_data']);

require_once '../includes/header.php';
?>

<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card" style="max-width: 500px;">
            <div class="auth-header">
                <img src="<?php echo BASE_URL; ?>img/logo1.png" alt="Logo" class="mb-3 mt-3" style="max-height: 60px;">
                <h3 style="margin-bottom: 0.2rem;">Conta Criada com Sucesso!</h3>
                <p class="mb-0">Parabéns, <?php echo htmlspecialchars($nome); ?>!</p>
            </div>
            <div class="auth-body-content">
                <?php include '../includes/auth-alerts.php'; ?>
                
                <div class="alert alert-success mb-3">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Registo concluído!</strong>
                    <p class="mb-0 mt-2">A sua conta foi criada com sucesso.</p>
                </div>
                
                <div class="alert alert-info mb-3">
                    <i class="fas fa-envelope me-2"></i>
                    <strong>Confirme o seu email</strong>
                    <p class="mb-2 mt-2">Enviámos um email de confirmação para:</p>
                    <p class="mb-0"><strong><?php echo htmlspecialchars($email); ?></strong></p>
                    <p class="mb-0 mt-2">Clique no link no email para confirmar a sua conta antes de iniciar sessão.</p>
                </div>
                
                <div class="text-center">
                    <p class="text-muted mb-2" style="font-size: 0.9rem;">
                        Não recebeu o email? Verifique a pasta de spam ou
                    </p>
                    
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-secondary" 
                                data-action="resend-confirmation" 
                                data-email="<?php echo htmlspecialchars($email); ?>">
                            <i class="fas fa-paper-plane me-1"></i>Reenviar Confirmação
                        </button>
                        <button type="button" class="btn btn-outline-info" 
                                data-action="check-verification" 
                                data-email="<?php echo htmlspecialchars($email); ?>">
                            <i class="fas fa-sync me-1"></i>Verificar Status
                        </button>
                        <a href="../login.php" class="btn btn-auth">
                            <i class="fas fa-sign-in-alt me-1"></i>Ir para Login
                        </a>
                    </div>
                    
                    <hr class="my-3">
                    <p class="mb-1" style="font-size: 0.9rem;">Precisa de ajuda?</p>
                    <a href="../pages/resend-confirmation.php" class="text-decoration-none">
                        <i class="fas fa-envelope me-1"></i>Centro de Confirmação
                    </a>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isEmailJSConfigured()): ?>
    // Initialize EmailJS
    emailjs.init('<?php echo EMAILJS_PUBLIC_KEY; ?>');
    
    // Send confirmation email
    const confirmationLink = window.location.origin + '/admin/pages/confirm-email.php?token=<?php echo $token; ?>';
    
    const templateParams = {
        user_name: '<?php echo addslashes($nome); ?>',
        to_email: '<?php echo addslashes($email); ?>',
        confirmation_link: confirmationLink,
        app_name: '<?php echo APP_NAME; ?>'
    };
    
    emailjs.send('<?php echo EMAILJS_SERVICE_ID; ?>', '<?php echo EMAILJS_TEMPLATE_EMAIL_CONFIRMATION; ?>', templateParams)
        .then(function(response) {
            console.log('Confirmation email sent successfully!');
        })
        .catch(function(error) {
            console.error('Failed to send confirmation email:', error);
            // Show error message to user
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-warning mt-3';
            alertDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i> <strong>Atenção:</strong> Não foi possível enviar o email de confirmação automaticamente. <a href="resend-confirmation.php" class="alert-link">Clique aqui para reenviar</a>.';
            document.querySelector('.auth-body-content').appendChild(alertDiv);
        });
    
    <?php else: ?>
    // Show warning if EmailJS is not configured
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-warning mt-3';
    alertDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i> <strong>Atenção:</strong> O sistema de email não está configurado. <a href="resend-confirmation.php" class="alert-link">Clique aqui para configurar</a>.';
    document.querySelector('.auth-body-content').appendChild(alertDiv);
    <?php endif; ?>
    
    // Button functionality
    document.querySelectorAll('[data-action]').forEach(button => {
        button.addEventListener('click', function() {
            const action = this.dataset.action;
            const email = this.dataset.email;
            
            if (action === 'resend-confirmation') {
                // Redirect to resend confirmation page
                window.location.href = `resend-confirmation.php?email=${encodeURIComponent(email)}`;
            } else if (action === 'check-verification') {
                // Check verification status via AJAX
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Verificando...';
                this.disabled = true;
                
                // Send AJAX request
                fetch('../api/check-email-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `email=${encodeURIComponent(email)}`
                })
                .then(response => response.json())
                .then(data => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                    
                    // Remove any existing status alerts
                    const existingAlerts = document.querySelectorAll('.alert-status');
                    existingAlerts.forEach(alert => alert.remove());
                    
                    // Show status message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-dismissible fade show mt-3 alert-status';
                    
                    if (data.status === 'confirmed') {
                        alertDiv.className += ' alert-success';
                        alertDiv.innerHTML = `
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Confirmado!</strong> ${data.message}
                            <a href="../login.php" class="btn btn-success btn-sm ms-2">Iniciar Sessão</a>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        `;
                    } else if (data.status === 'pending') {
                        alertDiv.className += ' alert-info';
                        alertDiv.innerHTML = `
                            <i class="fas fa-clock me-2"></i>
                            <strong>Pendente:</strong> ${data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        `;
                    } else if (data.status === 'expired') {
                        alertDiv.className += ' alert-warning';
                        alertDiv.innerHTML = `
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Expirado:</strong> ${data.message}
                            <a href="resend-confirmation.php?email=${encodeURIComponent(email)}" class="btn btn-warning btn-sm ms-2">Reenviar</a>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        `;
                    } else {
                        alertDiv.className += ' alert-danger';
                        alertDiv.innerHTML = `
                            <i class="fas fa-times-circle me-2"></i>
                            <strong>Erro:</strong> ${data.message || 'Erro desconhecido'}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        `;
                    }
                    
                    document.querySelector('.auth-body-content').appendChild(alertDiv);
                })
                .catch(error => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                    
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger mt-3 alert-dismissible fade show alert-status';
                    alertDiv.innerHTML = `
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Erro:</strong> Não foi possível verificar o status.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.querySelector('.auth-body-content').appendChild(alertDiv);
                });
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
