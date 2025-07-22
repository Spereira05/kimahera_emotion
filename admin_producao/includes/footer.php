    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>assets/js/auth.js"></script>
    
    <!-- EmailJS Configuration -->
    <script>
    <?php if (isEmailJSConfigured()): ?>
        window.emailJSConfig = <?php echo json_encode(getEmailJSConfig()); ?>;
    <?php else: ?>
        window.emailJSConfig = { enabled: false };
    <?php endif; ?>
    </script>
</body>
</html>
