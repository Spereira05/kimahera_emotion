<?php

$id_alunos = $_GET['id_alunos'];

echo $emotionController->alunos('`alunos`.`nome` ASC', '');
echo "<div id='aluno'>".$emotionController->perfilAluno($id_alunos)."</div>";

?>