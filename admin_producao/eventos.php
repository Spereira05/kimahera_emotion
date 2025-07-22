<?php

try {
    // Check if the controller and model are properly loaded
    if (!isset($emotionController) || !isset($emotionModel)) {
        echo "
            <div class='alert alert-danger' role='alert'>
                <h4 class='alert-heading'>Erro de inicialização</h4>
                <p>Controlador ou modelo não foram carregados corretamente.</p>
            </div>
        ";
        exit();
    }

    // Get the eventos HTML
    $eventosHTML = $emotionController->eventos();

    // Check if we got valid HTML
    if (empty($eventosHTML)) {
        echo "
            <div class='alert alert-warning' role='alert'>
                <h4 class='alert-heading'>Nenhum evento encontrado</h4>
                <p>Não existem eventos cadastrados no momento ou ocorreu um erro ao carregar os dados.</p>
            </div>
        ";
    } else {
        echo "<div class='container-fluid'>";
        echo "<div class='row'>";
        echo "<div class='col-12'>";
        echo "<h2 class='titulo mb-4'>Eventos</h2>";
        echo $eventosHTML;
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "
        <div class='alert alert-danger' role='alert'>
            <h4 class='alert-heading'>Erro</h4>
            <p>Ocorreu um erro ao carregar os eventos: " .
        htmlspecialchars($e->getMessage()) .
        "</p>
        </div>
    ";
}

?>
