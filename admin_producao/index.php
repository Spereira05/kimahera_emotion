<?php

// Redirect to new authentication system if not authenticated
require_once('config.php');
require_once('includes/functions.php');

error_reporting(E_ALL);
ini_set("display_errors", "On");

session_start();

// Check if user is logged in with new system
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// If logged in, continue with original dashboard logic
// Note: Error suppression is already configured in config.php

// Old authentication check - now handled by new system
/*
if(!isset($_SESSION['autenticado']))
{
	exit;
}
*/

/*
#IP Check
$allowedIPs = ['84.90.68.224', '2001:8a0:75a4:3e00:', '85.246.3.212'];
$userIP = $_SERVER['REMOTE_ADDR'];

if(strlen($userIP > 19))
{
    $userIP = substr($userIP, 0, 19);
}

if(!in_array($userIP, $allowedIPs))
{
    exit("Unauthorized: {$_SERVER['REMOTE_ADDR']}");
}
#/IP Check
*/

if(isset($_GET['p']))
{
    $_SESSION['p'] = $_GET['p'];
}
elseif(!isset($_SESSION['p']))
{
    $_SESSION['p'] = 'turmas'; #Default /#
}

require("model.php");
require("controller.php");
$emotionModel = new EmotionModel();
$emotionController = new EmotionController();

$rand = mt_rand();
//$rand = 0;

?>
<html class="h-100">
    <head>
        <title>Emotion Dance Academy</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
        <script type="text/javascript" src="script.js?id=<?php echo $rand; ?>"></script>
        <script src="https://kit.fontawesome.com/46c22e2ebb.js" crossorigin="anonymous"></script>
        <link rel="stylesheet" type="text/css" href="style.css?id=<?php echo $rand; ?>">
        <link rel="stylesheet" type="text/css" href="assets/css/bootstrap-header.css?id=<?php echo $rand; ?>">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="manifest" href="/site.webmanifest">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"> <!-- Santiago 18/07 18:22 -->
    </head>
    <body class="h-100">
        <div class="container p-5 h-100">
            <div class="row">
                <div class="col">
                    <header class="mb-2">
                        <!-- Header com grid system do Bootstrap -->
                        <div class="container-fluid">
                            <div class="row align-items-start" style="min-height: 40px;">
                                <!-- Espaçador esquerdo -->
                                <div class="col-md-3 d-none d-md-block"></div>
                                <!-- Logo centralizado -->
                                <div class="col-12 col-md-6 text-center">
                                    <div class="logo" style="margin-top: -10px;">
                                        <img src="img/logo_emotion.svg" alt="Emotion Dance Academy" 
                                             class="img-fluid" 
                                             style="max-height: 60px;" 
                                             onerror="this.src='img/logo.png'">
                                    </div>
                                </div>
                                <!-- User info à direita -->
                                <div class="col-12 col-md-3 text-center text-md-end mt-2 mt-md-0">
                                    <div class="user-info" style="margin-top: 40px;">
                                        <span class="text-muted d-block d-md-inline me-md-2">
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>
                                        </span>
                                        <a href="auth/logout.php" class="btn btn-outline-danger btn-sm mt-1 mt-md-0">
                                            <i class="fas fa-sign-out-alt me-1"></i>Sair
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </header>
                    <ul class="nav nav-pills mt-3 mb-3 bg_vermelho">
                    <?php

                    $paginas = array('dashboard' => 'Dashboard', 'alunos' => 'Alunos', 'professores' => 'Professores', 'professor' => '', 'aluno' => '', 'horario' => 'Horário', 'turmas' => 'Presenças', 'aulas' => 'Aulas', 'eventos' => 'Eventos');
                    foreach($paginas as $pagina => $titulo)
                    {
                        // Skip empty titles (hidden pages)
                        if(empty($titulo)) continue;
                        
                        //$active = '';
                        echo 
                        "
                            <li class='nav-item'>
                                <a class='nav-link beje' href='?p={$pagina}'>{$titulo}</a>
                            </li>
                        ";
                    }

                    ?>
                    </ul>
                </div>
            </div>

            <?php include("{$_SESSION['p']}.php"); ?>
            <div class='pb-5 w-100'></div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    </body>
</html>