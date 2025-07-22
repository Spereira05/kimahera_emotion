<?php

require_once('controller.php');
$emotionController = new EmotionController();

// Validate parameters
$id_aulas = isset($_GET['id_aulas']) && is_numeric($_GET['id_aulas']) ? (int)$_GET['id_aulas'] : 0;
$data = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    $data = date('Y-m-d');
}

// Only process if we have valid parameters
if ($id_aulas > 0) {
    echo "<div id='aula'>".$emotionController->aula($id_aulas, $data)."</div>";
} else {
    echo "<div class='alert alert-danger'>Erro: ID da aula não fornecido ou inválido.</div>";
}

?>