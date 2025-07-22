<?php

try {
    // Validate and sanitize input
    if (
        !isset($_GET["id_eventos"]) ||
        empty($_GET["id_eventos"]) ||
        !is_numeric($_GET["id_eventos"])
    ) {
        echo "
            <div class='container'>
                <div class='row bg_azul p-3 align-middle'>
                    <div class='col-8 text-left p-0'>
                        <h3 class='beje m-0'>Erro de Parâmetro</h3>
                    </div>
                    <div class='col-4 text-end p-0'>
                        <a href='?p=eventos' class='btn btn-outline-light btn-sm'>← Voltar aos Eventos</a>
                    </div>
                </div>
                <div class='row mt-3'>
                    <div class='col-12'>
                        <div class='alert alert-warning' role='alert'>
                            <h4 class='alert-heading'>Atenção!</h4>
                            <p>ID do evento não foi fornecido ou é inválido. Por favor, acesse um evento válido através da lista de eventos.</p>
                        </div>
                    </div>
                </div>
            </div>
        ";
        exit();
    }

    $id_eventos = (int) $_GET["id_eventos"];

    // Check if controller is loaded
    if (!isset($emotionController)) {
        echo "
            <div class='alert alert-danger' role='alert'>
                <h4 class='alert-heading'>Erro de Sistema</h4>
                <p>Sistema não inicializado corretamente. Por favor, recarregue a página.</p>
            </div>
        ";
        exit();
    }

    // Get the evento HTML
    $eventoHTML = $emotionController->evento($id_eventos);

    // Check if we got valid HTML
    if (empty($eventoHTML)) {
        echo "
            <div class='container'>
                <div class='row bg_azul p-3 align-middle'>
                    <div class='col-8 text-left p-0'>
                        <h3 class='beje m-0'>Evento Não Encontrado</h3>
                    </div>
                    <div class='col-4 text-end p-0'>
                        <a href='?p=eventos' class='btn btn-outline-light btn-sm'>← Voltar aos Eventos</a>
                    </div>
                </div>
                <div class='row mt-3'>
                    <div class='col-12'>
                        <div class='alert alert-warning' role='alert'>
                            <h4 class='alert-heading'>Evento não encontrado</h4>
                            <p>O evento solicitado não existe ou foi removido do sistema.</p>
                        </div>
                    </div>
                </div>
            </div>
        ";
    } else {
        echo "<div id='evento'>" . $eventoHTML . "</div>";
    }
} catch (Exception $e) {
    echo "
        <div class='alert alert-danger' role='alert'>
            <h4 class='alert-heading'>Erro</h4>
            <p>Ocorreu um erro ao carregar o evento: " .
        htmlspecialchars($e->getMessage()) .
        "</p>
            <hr>
            <p class='mb-0'><a href='?p=eventos' class='btn btn-primary'>← Voltar aos Eventos</a></p>
        </div>
    ";
}

?>
