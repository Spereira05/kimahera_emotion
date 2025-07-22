<?php 
require_once '../config.php';
require_once '../includes/functions.php';
session_start();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

$pageTitle = 'Recuperar Senha';
?>
<?php include '../includes/header.php'; ?>
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">

<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card" style="max-width: 420px;">
            <div class="auth-header">
                <img src="<?php echo BASE_URL; ?>img/logo1.png" alt="Logo" class="mb-3 mt-3" style="max-height: 60px;">
                <h3 style="margin-bottom: 0.2rem;">Recuperar Palavra-passe</h3>
                <p class="mb-0">Insira o seu email para receber o link de recuperação</p>
            </div>
            <div class="auth-body-content">
                <?php include '../includes/auth-alerts.php'; ?>
                
                <form action="../auth/forgot-password.php" method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-1"></i>Email
                        </label>
                        <input type="email" class="form-control" id="email" name="email" required 
                               autocomplete="email">
                        <div class="invalid-feedback">
                            Por favor, insira um email válido.
                        </div>
                        <div class="form-text">
                            Receberá um email com instruções para redefinir a sua palavra-passe.
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-auth w-100 mb-3" id="submitBtn">
                        <i class="fas fa-paper-plane me-1"></i>Enviar Link de Recuperação
                    </button>
                </form>
                
                <div class="text-center">
                    <?php if (isset($_SESSION['unconfirmed_email'])): ?>
                        <div class="mb-3">
                            <a href="resend-confirmation.php?email=<?php echo urlencode($_SESSION['unconfirmed_email']); ?>" class="btn btn-outline-info">
                                <i class="fas fa-envelope me-1"></i>Reenviar Confirmação
                            </a>
                        </div>
                        <?php unset($_SESSION['unconfirmed_email']); ?>
                    <?php endif; ?>
                    
                    <a href="../login.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i>Voltar ao Início de Sessão
                    </a>
                </div>
            </div>
        </div>
    </div>

<!-- EmailJS CDN -->
<script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configuração do EmailJS
    <?php 
    $emailConfig = getEmailJSConfig();
    if ($emailConfig['enabled']): ?>
        
        // Inicializar EmailJS
        emailjs.init('<?php echo $emailConfig['public_key']; ?>');
          
        // Verificar se há dados de email para enviar
        <?php if (isset($_SESSION['reset_email_data'])): ?>
            const emailData = <?php echo json_encode($_SESSION['reset_email_data']); ?>;
            
            // Enviar email automaticamente
            setTimeout(() => {
                sendPasswordResetEmail(emailData);
            }, 1000);
            
            <?php unset($_SESSION['reset_email_data']); ?>
        <?php endif; ?>
        
        function sendPasswordResetEmail(data) {
            const templateParams = {
                user_email: data.email,
                user_name: data.user_name,
                reset_link: data.reset_link,
                app_name: data.app_name,
                expires_at: data.expires_at,
                to_email: data.email
            };
            
            emailjs.send(
                '<?php echo $emailConfig['service_id']; ?>',
                '<?php echo $emailConfig['template_id']; ?>',
                templateParams
            ).then(
                (response) => {
                    // Email enviado com sucesso
                },
                (error) => {
                    // Falha silenciosa - o usuário já foi informado que receberá um email
                }
            );
        }
        
    <?php endif; ?>
      
    // Validação de formulário e feedback de envio
    const form = document.querySelector('form');
    const submitBtn = document.getElementById('submitBtn');
    const originalBtnContent = submitBtn ? submitBtn.innerHTML : '';
    
    if (form && submitBtn) {
        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Enviando...';
            
            // Reabilitar após 5 segundos como fallback
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnContent;
            }, 5000);
        });
    }
    
    // Auto-scroll para alertas após redirecionamento
    const alertElements = document.querySelectorAll('#errorAlert, #successAlert');
    if (alertElements.length > 0) {
        setTimeout(() => {
            alertElements[0].scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }, 100);
    }
    
    // Validação de email em tempo real
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('input', function() {
            const email = this.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else if (email) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            } else {
                this.classList.remove('is-valid', 'is-invalid');
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
