// Main JavaScript file for Login System

document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.auth-card, .dashboard-card');
    cards.forEach(function(card) {
        card.classList.add('fade-in');
    });

    // Password confirmation validation
    const confirmarSenha = document.getElementById('confirmar_senha');
    if (confirmarSenha) {
        confirmarSenha.addEventListener('input', function() {
            const senha = document.getElementById('senha').value;
            const confirmarSenhaValue = this.value;
              if (senha !== confirmarSenhaValue) {
                this.setCustomValidity('As palavras-passe não coincidem');
            } else {
                this.setCustomValidity('');
            }
        });
    }

    // Form validation styling
    const forms = document.querySelectorAll('.needs-validation');    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
            }
            form.classList.add('was-validated');
        });    });
});
