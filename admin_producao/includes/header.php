<!DOCTYPE html>
<html lang="pt" style="background: #a62a4c;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <style>
        /* CSS crítico inline para evitar flash branco */
        html, body {
            background: #a62a4c !important;
            margin: 0;
            padding: 0;
        }
        .auth-body {
            background: linear-gradient(135deg, #a62a4c 0%, #8b4a6b 25%, #d4527a 50%, #c1476f 75%, #f4e4e8 100%) !important;
            min-height: 100vh;
            animation: fadeInBackground 0.8s ease-in-out;
        }
        @keyframes fadeInBackground {
            0% { 
                background: #a62a4c; 
                opacity: 0.9;
            }
            100% { 
                background: linear-gradient(135deg, #a62a4c 0%, #8b4a6b 25%, #d4527a 50%, #c1476f 75%, #f4e4e8 100%);
                opacity: 1;
            }
        }
    </style>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>assets/css/auth.css" rel="stylesheet">
    
    <!-- EmailJS -->
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
    
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
</head>
