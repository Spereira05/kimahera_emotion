<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$id_professores = $_GET['id_professores'];

echo "<div id='professor'>".$emotionController->perfilProfessor($id_professores)."</div>";

?>
