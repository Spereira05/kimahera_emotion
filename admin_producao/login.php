<?php 
session_start();
require_once 'config.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$pageTitle = 'Iniciar Sessão';
?>
<?php include 'includes/header.php'; ?>

<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <img src="<?php echo BASE_URL; ?>img/logo1.png" alt="Logo" class="mb-3 mt-3" style="max-height: 60px;">
                <h3 style="margin-bottom: 0.2rem;">Bem-vindo</h3>
                <p class="mb-0">Inicie sessão na sua conta</p>
            </div>
            <div class="auth-body-content">
                <?php include 'includes/auth-alerts.php'; ?>
                
                <form action="auth/login.php" method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-1"></i>Email
                        </label>
                        <input type="email" class="form-control" id="email" name="email" required 
                               autocomplete="email">
                        <div class="invalid-feedback">
                            Por favor, insira um email válido.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="senha" class="form-label">
                            <i class="fas fa-lock me-1"></i>Palavra-passe
                        </label>
                        <input type="password" class="form-control" id="senha" name="senha" required 
                               autocomplete="current-password">
                        <div class="invalid-feedback">
                            Por favor, insira a sua palavra-passe.
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-auth w-100 mb-3">
                        <i class="fas fa-sign-in-alt me-1"></i>Entrar
                    </button>
                </form>
                
                <div class="text-center">
                    <a href="pages/forgot-password.php" class="text-decoration-none d-block mb-2">
                        <i class="fas fa-key me-1"></i>Esqueceu-se da palavra-passe?
                    </a>
                    <!-- <hr class="my-3">
                    <p class="mb-1" style="font-size: 0.9rem;">Não tem uma conta?</p>
                    <a href="pages/register.php" class="text-decoration-none">
                        <i class="fas fa-user-plus me-1"></i>Criar conta
                    </a> -->
                </div>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
