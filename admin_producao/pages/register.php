<?php 
require_once '../config.php';
require_once '../includes/functions.php';
session_start();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

$pageTitle = 'Criar Conta';
?>
<?php include '../includes/header.php'; ?>
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">

<body class="auth-body">    <div class="auth-container">
        <div class="auth-card" style="max-width: 450px;">            <div class="auth-header">
                <img src="<?php echo BASE_URL; ?>img/logo1.png" alt="Logo" class="mb-3 mt-3" style="max-height: 60px;">
                <h3 style="margin-bottom: 0.2rem;">Criar Conta</h3>
                <p class="mb-0">Junte-se a nós hoje</p>
            </div>
            <div class="auth-body-content">
                <?php include '../includes/auth-alerts.php'; ?>
                
                <form action="../auth/register.php" method="POST" class="needs-validation" novalidate>                    <div class="mb-2">
                        <label for="nome" class="form-label">
                            <i class="fas fa-user me-1"></i>Nome Completo
                        </label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                        <div class="invalid-feedback">
                            Por favor, insira o seu nome completo.
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-1"></i>Email
                        </label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="invalid-feedback">
                            Por favor, insira um email válido.
                        </div>                    </div>
                    
                    <div class="mb-2">
                        <label for="senha" class="form-label">
                            <i class="fas fa-lock me-1"></i>Palavra-passe
                        </label>
                        <input type="password" class="form-control" id="senha" name="senha" required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                        <div class="form-text">A palavra-passe deve ter pelo menos <?php echo PASSWORD_MIN_LENGTH; ?> caracteres.</div>
                        <div class="invalid-feedback">
                            A palavra-passe deve ter pelo menos <?php echo PASSWORD_MIN_LENGTH; ?> caracteres.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirmar_senha" class="form-label">
                            <i class="fas fa-lock me-1"></i>Confirmar Palavra-passe
                        </label>
                        <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                        <div class="invalid-feedback">
                            As palavras-passe devem coincidir.
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-auth w-100 mb-2">
                        <i class="fas fa-user-plus me-1"></i>Criar Conta
                    </button>
                </form>
                
                <div class="text-center">
                    <p class="mb-1" style="font-size: 0.9rem;">Já tem uma conta?</p>
                    <a href="../login.php" class="text-decoration-none">Iniciar sessão</a>
                </div>
            </div>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>
