<?php

echo $emotionController->professores();
echo "<div id='aulas'>".$emotionController->aulasLista('', '')."</div>";

// Botão de exportação para PDF
$id_professores = isset($_GET['id_professores']) ? $_GET['id_professores'] : '';
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

if ($id_professores) {
    echo "<div class='w-100 text-end mt-3'>
        <form method='get' action='exporta_aulas_professor_fpdf.php' target='_blank' class='d-inline'>
            <input type='hidden' name='id_professores' value='{$id_professores}'>
            <input type='hidden' name='data_inicio' value='{$data_inicio}'>
            <input type='hidden' name='data_fim' value='{$data_fim}'>
            <button type='submit' class='btn btn-danger'><i class='fas fa-file-pdf me-2'></i>Exportar PDF</button>
        </form>
    </div>";
}

?>