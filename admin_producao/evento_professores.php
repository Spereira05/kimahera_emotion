<?php

if (!isset($_GET["id_eventos"]) || empty($_GET["id_eventos"])) {
    echo "
        <div class='container'>
            <div class='row bg_azul p-3 align-middle'>
                <div class='col-8 text-left p-0'>
                    <h3 class='beje m-0'>Parâmetro inválido</h3>
                </div>
                <div class='col-4 text-end p-0'>
                    <a href='?p=eventos' class='btn btn-outline-light btn-sm'>← Voltar aos Eventos</a>
                </div>
            </div>
            <div class='row mt-3'>
                <div class='col-12'>
                    <div class='alert alert-warning' role='alert'>
                        <h4 class='alert-heading'>Atenção!</h4>
                        <p>ID do evento não foi fornecido. Por favor, acesse um evento válido através da lista de eventos.</p>
                    </div>
                </div>
            </div>
        </div>
    ";
} else {
    $id_eventos = $_GET["id_eventos"];
    echo "<div id='evento-professores'>" .
        $emotionController->eventosProfessores($id_eventos) .
        "</div>";
}

?>
