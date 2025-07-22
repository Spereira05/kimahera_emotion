<?php

require("config.php");

echo $emotionController->turmas();
echo "<div class='turma'>";

// Validate parameters before using them
$id_aulas = isset($_GET['id_aulas']) && is_numeric($_GET['id_aulas']) ? (int)$_GET['id_aulas'] : 0;
$dia = isset($_GET['dia']) ? $_GET['dia'] : '';
$data = isset($_GET['data']) ? $_GET['data'] : '';

// Only call turma if we have a valid ID
if ($id_aulas > 0) {
    echo $emotionController->turma($id_aulas, $dia, $data);
}

echo "</div>";

?>