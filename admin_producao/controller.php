<?php

require_once "model.php";

class EmotionController
{
    public $emotionModel;
    public $id_alunos;
    public $id_professores;
    public $id_modalidades;
    public $data_inicio;
    public $data_fim;
    public $dias_semana = [
        "",
        "segunda",
        "terca",
        "quarta",
        "quinta",
        "sexta",
        "sabado",
        "domingo",
    ];

    public function __construct()
    {
        $this->emotionModel = new EmotionModel();
        $this->initializeProperties();
    }

    /**
     * Safely extract data from database result
     * @param mysqli_result|false $result
     * @return array|false
     */
    private function safeExtract($result)
    {
        if (!$result || $result->num_rows == 0) {
            return false;
        }

        $row = $result->fetch_array();

        if (!$row || !is_array($row)) {
            return false;
        }

        return $row;
    }

    // Initialize class properties
    private function initializeProperties()
    {
        #Aluno
        if (isset($_GET["id_alunos"])) {
            $id_alunos = $_GET["id_alunos"];
        } else {
            $id_alunos = 0;
        }

        $this->id_alunos = $id_alunos;
        #/Aluno

        #Professor
        if (isset($_GET["id_professores"])) {
            $id_professores = $_GET["id_professores"];
        } else {
            $id_professores = 0;
        }

        $this->id_professores = $id_professores;
        #/Professor

        #Modalidade
        if (isset($_GET["id_modalidades"])) {
            $id_modalidades = $_GET["id_modalidades"];
        } else {
            $id_modalidades = 0;
        }

        $this->id_modalidades = $id_modalidades;
        #/Professor

        #Data de início e fim
        if (isset($_GET["data_inicio"])) {
            $data_inicio = $_GET["data_inicio"];
        }
        if (isset($_GET["data_fim"])) {
            $data_fim = $_GET["data_fim"];
        }
        if (!isset($data_inicio)) {
            $data_inicio = $data_fim = date("Y-m-d");
        } #Hoje /#
        $this->data_inicio = $data_inicio;
        $this->data_fim = $data_fim;
        #/Data de início e fim
    }

    function api()
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api10.toconline.pt/api/customers",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer 10-202422-832791-4d7ce8ff12090e08b595362c12b5cf95e6c2366f678db410ccc3d4c9dfb4d652",
            ],
        ]);

        $response = curl_exec($curl);
        $data = json_decode($response, true);

        foreach ($data as $r) {
            foreach ($r as $row) {
                $id = $row["id"];
                $nif = $row["attributes"]["tax_registration_number"];
                $nome = $row["attributes"]["business_name"];

                $clientes[$nif] = ["id" => $id, "nome" => $nome];
                $clientes[$nome] = ["id" => $id, "nome" => $nome];
            }
        }

        //$this->emotionModel->clientes($clientes);

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api10.toconline.pt/api/commercial_sales_documents?filter[status]=1",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer 10-202422-832791-4d7ce8ff12090e08b595362c12b5cf95e6c2366f678db410ccc3d4c9dfb4d652",
            ],
        ]);

        $response = curl_exec($curl);
        $data = json_decode($response, true);

        foreach ($data as $r) {
            // API data processing removed - replace with actual implementation if needed
        }

        //$this->emotionModel->faturas();

        curl_close($curl);
    }

    function dashboard()
    {
        $dash = "";

        #Assiduidade Modalidades
        $n = 0;
        $total_antes = 0;
        $ass_mod = "";
        $result = $this->emotionModel->assiduidadeModalidades();
        while ($row = $result->fetch_array()) {
            extract($row);

            $modalidade = $nome;
            if ($abreviatura) {
                $modalidade = $abreviatura;
            }

            if ($assiduidade != $total_antes) {
                $n++;
            }

            $ass = $this->assiduidade("", "", $assiduidade);
            $ass_mod .=
                "
                <li class='list-group-item align-items-end'>
                    <div class='d-inline-block'><small>" .
                sprintf("%02d", $n) .
                ". {$modalidade}</small></div>
                    <div class='d-inline-block'>{$ass[1]}</div>
                </li>
            ";

            $total_antes = $assiduidade;
        }
        #/Assiduidade Modalidades

        #Assiduidade Alunos Ausentes
        $n = 0;
        $total_antes = 0;
        $ass_alunos_ausentes = "";
        $result = $this->emotionModel->alunos(
            "`assiduidade` ASC, `total` DESC, `alunos`.`nome`",
            10,
        );
        while ($row = $result->fetch_array()) {
            extract($row);

            $nome = $this->nomeAluno($nome, $alcunha);

            if ($total != $total_antes) {
                $n++;
            }

            $ass = $this->assiduidade("", "", $assiduidade);
            $ass_alunos_ausentes .=
                "
                <li class='list-group-item'>
                    <div class='d-inline-block'><small>" .
                sprintf("%02d", $n) .
                ". {$nome}</small></div>
                    <div class='d-inline-block'>{$ass[1]}</div>
                </li>
            ";

            $total_antes = $total;
        }
        #/Assiduidade Alunos Ausentes

        #Assiduidade Alunos Presentes
        $n = 0;
        $total_antes = 0;
        $ass_alunos_presentes = "";
        $result = $this->emotionModel->alunos(
            "`assiduidade` DESC, `total` DESC, `alunos`.`nome`",
            10,
        );
        while ($row = $result->fetch_array()) {
            extract($row);

            $nome = $this->nomeAluno($nome, $alcunha);

            if ($total != $total_antes) {
                $n++;
            }

            $ass = $this->assiduidade("", "", $assiduidade);
            $ass_alunos_presentes .=
                "
                <li class='list-group-item'>
                    <div class='d-inline-block'><small>" .
                sprintf("%02d", $n) .
                ". {$nome}</small></div>
                    <div class='d-inline-block'>{$ass[1]}</div>
                </li>
            ";

            $total_antes = $total;
        }
        #/Assiduidade Alunos Presentes

        #Alunos Localidades
        $n = 0;
        $total_antes = 0;
        $alunos_localidades = "";
        $result = $this->emotionModel->alunosLocalidades();
        while ($row = $result->fetch_array()) {
            extract($row);

            if ($alunos != $total_antes) {
                $n++;
            }

            $alunos_localidades .=
                "
                <li class='list-group-item'>
                    <div class='d-inline-block'><small>" .
                sprintf("%02d", $n) .
                ". {$localidade}</small></div>
                    <div class='d-inline-block'>
                        <div class='progress'>
                            <div class='progress-bar bg_vermelho' role='progressbar' style='width: 100%;' aria-valuenow='100' aria-valuemin='0' aria-valuemax='100'>{$alunos}</div>
                        </div>
                    </div>
                </li>
            ";

            $total_antes = $alunos;
        }
        #/Alunos Localidades

        $col = 4;

        $dash = "
            <div class='row p-0 m-0'>

                <div class='card-columns col-{$col} m-0 p-0'>
                    <div class='card'>
                        <div class='card-header fw-bold bg_beje'>
                            Top Assiduidade Modalidades
                        </div>
                        <ul class='list-group list-group-flush'>
                            {$ass_mod}
                        </ul>
                    </div>
                </div>

                <div class='card-columns col-{$col} m-0'>

                    <div class='card'>
                        <div class='card-header fw-bold bg_beje'>
                            Top 10 Alunos <span class='text-success'>Presentes</span>
                        </div>
                        <ul class='list-group list-group-flush'>
                            {$ass_alunos_presentes}
                        </ul>
                    </div>

                    <div class='card mt-3'>
                        <div class='card-header fw-bold bg_beje'>
                            Top 10 Alunos <span class='text-danger'>Ausentes</span>
                        </div>
                        <ul class='list-group list-group-flush'>
                            {$ass_alunos_ausentes}
                        </ul>
                    </div>

                </div>

                <div class='card-columns col-{$col} m-0 p-0'>
                    <div class='card'>
                        <div class='card-header fw-bold bg_beje'>
                            Alunos por Localidade
                        </div>
                        <ul class='list-group list-group-flush'>
                            {$alunos_localidades}
                        </ul>
                    </div>
                </div>

            </div>
        ";

        return $dash;
    }

    function alunos($order, $limit)
    {
        $result = $this->emotionModel->alunos($order, $limit);

        $selected_student = "";
        $student_data = [];

        while ($row = $result->fetch_array()) {
            extract($row);
            $idade = $this->idade($data_nascimento);

            $student_info = [
                "id" => $id,
                "nome" => $nome,
                "idade" => $idade,
                "display" => "{$nome} ({$idade} anos) [{$id}]",
            ];

            $student_data[] = $student_info;

            if ($id == $this->id_alunos) {
                $selected_student = $student_info["display"];
            }
        }

        $students_json = json_encode($student_data);

        return "
            <form method='get' name='alunos' class='mb-3'>
                <div class='autocomplete-container position-relative'>
                    <input type='text'
                           id='student-search'
                           class='form-control'
                           placeholder='Pesquisar aluno por nome...'
                           value='{$selected_student}'
                           autocomplete='off'>
                    <input type='hidden' id='id_alunos' name='id_alunos' value='{$this->id_alunos}'>
                    <div id='autocomplete-results' class='autocomplete-results position-absolute w-100 bg-white border border-top-0 d-none' style='z-index: 1000; max-height: 300px; overflow-y: auto;'></div>
                </div>
            </form>

            <script>
            $(document).ready(function() {
                const students = {$students_json};
                const searchInput = $('#student-search');
                const hiddenInput = $('#id_alunos');
                const resultsContainer = $('#autocomplete-results');

                let selectedIndex = -1;

                // Search functionality
                searchInput.on('input', function() {
                    const query = $(this).val().toLowerCase();
                    selectedIndex = -1;

                    if (query.length < 2) {
                        resultsContainer.addClass('d-none').empty();
                        hiddenInput.val('');
                        return;
                    }

                    const filteredStudents = students.filter(student =>
                        student.nome.toLowerCase().includes(query) ||
                        student.id.toString().includes(query)
                    );

                    if (filteredStudents.length === 0) {
                        resultsContainer.addClass('d-none').empty();
                        return;
                    }

                    let html = '';
                    filteredStudents.forEach((student, index) => {
                        html += `<div class='autocomplete-item p-2 border-bottom cursor-pointer' data-id='\${student.id}' data-index='\${index}'>
                                    <strong>\${student.nome}</strong> <small class='text-muted'>(\${student.idade} anos) [\${student.id}]</small>
                                 </div>`;
                    });

                    resultsContainer.html(html).removeClass('d-none');
                });

                // Click selection
                $(document).on('click', '.autocomplete-item', function() {
                    const studentId = $(this).data('id');
                    const studentText = $(this).text().trim();

                    searchInput.val(studentText);
                    hiddenInput.val(studentId);
                    resultsContainer.addClass('d-none').empty();

                    // Submit form to reload page with selected student
                    $('form[name=\"alunos\"]').submit();
                });

                // Keyboard navigation
                searchInput.on('keydown', function(e) {
                    const items = $('.autocomplete-item');

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                        updateSelection(items);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        selectedIndex = Math.max(selectedIndex - 1, -1);
                        updateSelection(items);
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        if (selectedIndex >= 0 && items.length > 0) {
                            items.eq(selectedIndex).click();
                        }
                    } else if (e.key === 'Escape') {
                        resultsContainer.addClass('d-none').empty();
                        selectedIndex = -1;
                    }
                });

                function updateSelection(items) {
                    items.removeClass('bg-light');
                    if (selectedIndex >= 0) {
                        items.eq(selectedIndex).addClass('bg-light');
                    }
                }

                // Hide results when clicking outside
                $(document).on('click', function(e) {
                    if (!$(e.target).closest('.autocomplete-container').length) {
                        resultsContainer.addClass('d-none').empty();
                        selectedIndex = -1;
                    }
                });

                // Clear selection when input is cleared
                searchInput.on('keyup', function() {
                    if ($(this).val() === '') {
                        hiddenInput.val('');
                        resultsContainer.addClass('d-none').empty();
                    }
                });
            });
            </script>

            <style>
            .autocomplete-item {
                cursor: pointer;
                transition: background-color 0.2s ease;
            }
            .autocomplete-item:hover {
                background-color: #f8f9fa !important;
            }
            .autocomplete-results {
                border-radius: 0 0 0.375rem 0.375rem;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            #student-search:focus {
                border-bottom-left-radius: 0;
                border-bottom-right-radius: 0;
            }
            </style>
        ";
    }

    function professores()
    {
        $result = $this->emotionModel->professores();

        $professores = "<option value='0'>Professores</option>";
        while ($row = $result->fetch_array()) {
            extract($row);

            $selected = "";
            if ($id == $this->id_professores) {
                $selected = "selected";
            }

            if ($alcunha) {
                $nome = $alcunha;
            }

            $idade = $this->idade($data_nascimento);

            $professores .= "<option value='{$id}' {$selected}>{$nome} ({$idade} anos) [{$id}]</option>";
        }

        $modalidades = "<option value='0'>Modalidades</option>";
        if ($this->id_professores) {
            $result = $this->emotionModel->modalidadesProfessor(
                $this->id_professores,
                $this->horarioAtual(),
            );

            while ($row = $result->fetch_array()) {
                extract($row);

                $selected = "";
                if ($id == $this->id_modalidades) {
                    $selected = "selected";
                }

                $modalidades .= "<option value='{$id}' {$selected}>{$nome}</option>";
            }
        }

        return "
            <form method='get' name='professores'>
                <input name='p' type='hidden' value='aulas' />
                <div class='row mt-2'>
                    <div class='col-6 form-group'>
                        <select name='id_professores' class='form-select'>{$professores}</select>
                    </div>
                    <div class='col-6 form-group'>
                        <select name='id_modalidades' class='form-select'>{$modalidades}</select>
                    </div>
                </div>
                <div class='row mt-2'>
                    <div class='col-6 form-group'>
                        <input type='date' id='data_inicio' name='data_inicio' class='form-select' value='{$this->data_inicio}' />
                    </div>
                    <div class='col-6 form-group'>
                        <input type='date' id='data_fim' name='data_fim' class='form-select' value='{$this->data_fim}' />
                    </div>
                </div>
            </form>
        ";
    }

    function idade($data_nascimento)
    {
        $dob = new DateTime($data_nascimento);
        $today = new DateTime("today");
        $year = $dob->diff($today)->y;
        $idade = $year;

        return $idade;
    }

    function fotoAluno($id)
    {
        $rand = substr(
            str_shuffle(
                "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ",
            ),
            0,
            5,
        );
        $rand = date("dmY");
        //$rand = '';

        if (file_exists("alunos/{$id}.jpg")) {
            $foto = "<img src='alunos/{$id}.jpg?cache={$rand}' alt='' class='foto' />";
        } else {
            return false;
        }

        return $foto;
    }

    function fotoProfessor($id)
    {
        $rand = date("dmY");
        $caminho = "professores/{$id}.jpg";
        if (file_exists($caminho)) {
            return "<img src='{$caminho}?cache={$rand}' alt='' class='foto' />";
        } else {
            // Fallback para imagem padrão
            return "<img src='img/user-default.png' alt='Sem foto' class='foto' />";
        }
    }

    function fotoEvento($id)
    {
        $rand = date("dmY");

        // Check for multiple possible image extensions
        $possibleFiles = [
            "eventos/{$id}.jpg",
            "eventos/{$id}.jpeg",
            "eventos/{$id}.png",
            "eventos/{$id}.JPG",
        ];

        foreach ($possibleFiles as $filePath) {
            if (file_exists($filePath)) {
                return "<img src='{$filePath}?cache={$rand}' alt='Imagem do evento' class='foto-evento' style='width: 100%; max-width: 600px; height: 100%; border-radius: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); display: block; margin: 0 auto;' />";
            }
        }

        // If no image found, return placeholder
        return "<div class='foto-evento bg-light text-muted d-flex align-items-center justify-content-center' style='width: 100%; max-width: 100%; height: 250px; border-radius: 8px; border: 2px dashed #dee2e6; margin: 0 auto;'>" .
            "<div class='text-center'><i class='fas fa-image fa-3x'></i><br><small>Sem imagem</small></div>" .
            "</div>";
    }

    function alunosLista()
    {
        // Get pagination parameters
        $page = isset($_GET["page"]) ? max(1, intval($_GET["page"])) : 1;
        $per_page = 25;
        $offset = ($page - 1) * $per_page;

        // Get filter parameters
        $search_name = $_GET["search_name"] ?? "";
        $filter_idade_min = $_GET["filter_idade_min"] ?? "";
        $filter_idade_max = $_GET["filter_idade_max"] ?? "";
        $filter_aniversario_date = $_GET["filter_aniversario_date"] ?? "";
        $search_phone = $_GET["search_phone"] ?? "";

        // Get all students for filtering
        $all_students = [];
        $result = $this->emotionModel->alunos("`alunos`.`nome` ASC", "");
        while ($row = $result->fetch_array()) {
            $idade = $this->idade($row["data_nascimento"]);
            $student = array_merge($row, ["idade" => $idade]);
            $all_students[] = $student;
        }

        // Apply filters with search priority
        $filtered_students = [];
        foreach ($all_students as $student) {
            $match = true;
            $search_priority = 0; // Lower number = higher priority

            // Name search filter with priority scoring
            if (!empty($search_name)) {
                $search_lower = strtolower($search_name);
                $nome_lower = strtolower($student["nome"]);
                $alcunha_lower = !empty($student["alcunha"])
                    ? strtolower($student["alcunha"])
                    : "";

                $nome_match = strpos($nome_lower, $search_lower) !== false;
                $alcunha_match =
                    !empty($student["alcunha"]) &&
                    strpos($alcunha_lower, $search_lower) !== false;
                $id_match =
                    strpos((string) $student["id"], $search_name) !== false;

                if ($nome_match || $alcunha_match || $id_match) {
                    // Determine search priority (lower is better)
                    if ($id_match) {
                        $search_priority = 1; // ID match has highest priority
                    } elseif (strpos($nome_lower, $search_lower) === 0) {
                        $search_priority = 2; // First name starts with search
                    } elseif (strpos($alcunha_lower, $search_lower) === 0) {
                        $search_priority = 3; // Nickname starts with search
                    } elseif ($nome_match) {
                        // Check if it's first name vs last name
                        $name_parts = explode(" ", $nome_lower);
                        $first_name_match =
                            !empty($name_parts[0]) &&
                            strpos($name_parts[0], $search_lower) !== false;
                        if ($first_name_match) {
                            $search_priority = 4; // First name contains search
                        } else {
                            $search_priority = 6; // Last name contains search
                        }
                    } elseif ($alcunha_match) {
                        $search_priority = 5; // Nickname contains search
                    }
                } else {
                    $match = false;
                }
            }

            if ($match) {
                $student["search_priority"] = $search_priority;
                $filtered_students[] = $student;
            }

            // Phone search filter
            if (!empty($search_phone)) {
                $phone_match = false;
                $search_phone_clean = preg_replace("/\D/", "", $search_phone); // Remove non-digits

                // Check student phone
                if (!empty($student["telemovel"])) {
                    $student_phone_clean = preg_replace(
                        "/\D/",
                        "",
                        $student["telemovel"],
                    );
                    if (
                        strpos($student_phone_clean, $search_phone_clean) !==
                        false
                    ) {
                        $phone_match = true;
                    }
                }

                // Check guardian phone
                if (!$phone_match && !empty($student["telemovel_ee"])) {
                    $guardian_phone_clean = preg_replace(
                        "/\D/",
                        "",
                        $student["telemovel_ee"],
                    );
                    if (
                        strpos($guardian_phone_clean, $search_phone_clean) !==
                        false
                    ) {
                        $phone_match = true;
                    }
                }

                if (!$phone_match) {
                    $match = false;
                }
            }

            // Age filters
            if (
                !empty($filter_idade_min) &&
                $student["idade"] < intval($filter_idade_min)
            ) {
                $match = false;
            }
            if (
                !empty($filter_idade_max) &&
                $student["idade"] > intval($filter_idade_max)
            ) {
                $match = false;
            }

            // Birthday date filter
            if (!empty($filter_aniversario_date)) {
                $birthday_match = false;
                $data_nascimento = $student["data_nascimento"];

                // Compare month and day only (ignore year)
                $selected_date = date(
                    "m-d",
                    strtotime($filter_aniversario_date),
                );
                $student_birthday = date("m-d", strtotime($data_nascimento));

                if ($selected_date == $student_birthday) {
                    $birthday_match = true;
                }

                if (!$birthday_match) {
                    $match = false;
                }
            }
        }

        // Sort by search priority if there's a search term
        if (!empty($search_name)) {
            usort($filtered_students, function ($a, $b) {
                if ($a["search_priority"] !== $b["search_priority"]) {
                    return $a["search_priority"] - $b["search_priority"]; // Lower priority number comes first
                }
                return strcasecmp($a["nome"], $b["nome"]); // Alphabetical as secondary sort
            });
        }

        // Calculate totals for all filtered students
        $total_students = count($filtered_students);

        // Calculate pagination with 150 student threshold
        if ($total_students <= 150) {
            // Normal pagination with 25 per page
            $total_pages = ceil($total_students / $per_page);
            $page_students = array_slice(
                $filtered_students,
                $offset,
                $per_page,
            );
        } else {
            // Special handling: 25 per page until 150, then all remaining on last page
            $pages_of_25 = 6; // 6 pages of 25 = 150 students
            $students_in_first_pages = $pages_of_25 * $per_page; // 150 students
            $total_pages = $pages_of_25 + 1; // 6 pages + 1 final page

            if ($page <= $pages_of_25) {
                // Regular page with 25 students
                $page_students = array_slice(
                    $filtered_students,
                    $offset,
                    $per_page,
                );
            } else {
                // Final page with all remaining students (151+)
                $page_students = array_slice(
                    $filtered_students,
                    $students_in_first_pages,
                );
            }
        }

        // Calculate totals for current page only
        $page_total_mensalidades = 0;

        // Calculate overall totals for ALL students in the system (not filtered)
        $overall_total_mensalidades = 0;
        foreach ($all_students as $student) {
            $overall_total_mensalidades += $this->mensalidade($student["id"]);
        }

        // Build table body
        $tbody = "";
        foreach ($page_students as $row) {
            extract($row);

            $mensalidade = $this->mensalidade($id);
            $page_total_mensalidades += $mensalidade;
            $aniversario = date("M d", strtotime($data_nascimento));

            $ativo = "";
            if (date("m-d", strtotime($data_nascimento)) == date("m-d")) {
                $aniversario = "<span class='fw-bolder vermelho'>{$aniversario} 🥳</span>";
                $ativo = "ativo";
            }

            $nome_ee = explode(" ", $nome_ee);
            $contacto = $telemovel;
            if ($email_ee) {
                $email = $email_ee;
            }
            if ($telemovel_ee) {
                $contacto = "{$telemovel_ee} ({$nome_ee[0]})";
            }

            $nome = $this->nomeAluno($nome, $alcunha);
            $foto = $this->fotoAluno($id);
            $assiduidade = $this->assiduidade("", "", $assiduidade);

            $tbody .= "
                <tr class='{$ativo}' data-student-id='{$id}' data-student-name='{$nome}'>
                    <td>{$id}</td>
                    <td>{$foto}</td>
                    <td><a href='?p=aluno&id_alunos={$id}'>{$nome}</a></td>
                    <td>{$assiduidade[1]}</td>
                    <td>{$aniversario}</td>
                    <td>{$idade}</td>
                    <td>{$email}</td>
                    <td>{$contacto}</td>
                    <td>{$mensalidade}€</td>
                    <td><a href='pdf.php?id_alunos={$id}&generate=1' class='btn btn-primary btn-sm' target='_blank' title='Gerar Certificado'><i class='fas fa-certificate'></i></a></td>
                </tr>
            ";
        }

        // Build pagination (total_pages already calculated above)
        $pagination = "";

        if ($total_pages > 1) {
            $pagination =
                "<nav aria-label='Paginação de alunos'><ul class='pagination justify-content-center'>";

            // Previous button
            if ($page > 1) {
                $prev_page = $page - 1;
                $pagination .= "<li class='page-item'><a class='page-link bg_azul beje text-decoration-none' href='?p=alunos&page={$prev_page}&search_name={$search_name}&filter_idade_min={$filter_idade_min}&filter_idade_max={$filter_idade_max}&filter_aniversario_date={$filter_aniversario_date}&search_phone={$search_phone}'>Anterior</a></li>";
            }

            // Page numbers
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);

            for ($i = $start_page; $i <= $end_page; $i++) {
                if ($i == $page) {
                    // Active page - beje background with purple text
                    $pagination .= "<li class='page-item active'><a class='page-link bg_beje azul text-decoration-none' href='?p=alunos&page={$i}&search_name={$search_name}&filter_idade_min={$filter_idade_min}&filter_idade_max={$filter_idade_max}&filter_aniversario_date={$filter_aniversario_date}&search_phone={$search_phone}'>{$i}</a></li>";
                } else {
                    // Regular pages - purple background with beje text
                    $pagination .= "<li class='page-item'><a class='page-link bg_azul beje text-decoration-none' href='?p=alunos&page={$i}&search_name={$search_name}&filter_idade_min={$filter_idade_min}&filter_idade_max={$filter_idade_max}&filter_aniversario_date={$filter_aniversario_date}&search_phone={$search_phone}'>{$i}</a></li>";
                }
            }

            // Next button
            if ($page < $total_pages) {
                $next_page = $page + 1;
                $pagination .= "<li class='page-item'><a class='page-link bg_azul beje text-decoration-none' href='?p=alunos&page={$next_page}&search_name={$search_name}&filter_idade_min={$filter_idade_min}&filter_idade_max={$filter_idade_max}&filter_aniversario_date={$filter_aniversario_date}&search_phone={$search_phone}'>Próximo</a></li>";
            }

            $pagination .= "</ul></nav>";
        }

        // Build filter interface
        $filter_interface =
            "
        <div class='container-fluid py-4'>
            <div class='row mb-4'>
                <div class='col-12'>
                    <div class='card shadow-sm border-0'>
                        <div class='card-header bg_azul text-white py-3'>
                            <h5 class='mb-0 beje'>Filtros de Pesquisa</h5>
                        </div>
                        <div class='card-body'>
                            <form method='get' name='alunos'>
                                <input type='hidden' name='p' value='alunos'>
                                <div class='row g-3 align-items-end'>
                                    <div class='col-md-3'>
                                        <label for='search_name' class='form-label'>Pesquisar por Nome:</label>
                                        <input type='text' id='search_name' name='search_name' class='form-control' placeholder='Digite o nome do aluno...' value='{$search_name}'>
                                    </div>
                                    <div class='col-md-3'>
                                        <label for='search_phone' class='form-label'>Pesquisar por Telefone:</label>
                                        <input type='text' id='search_phone' name='search_phone' class='form-control' placeholder='Digite o número de telefone...' value='{$search_phone}'>
                                    </div>
                                    <div class='col-md-1'>
                                        <label for='filter_idade_min' class='form-label'>Idade Mín:</label>
                                        <input type='number' id='filter_idade_min' name='filter_idade_min' class='form-control' min='0' max='100' value='{$filter_idade_min}'>
                                    </div>
                                    <div class='col-md-1'>
                                        <label for='filter_idade_max' class='form-label'>Idade Máx:</label>
                                        <input type='number' id='filter_idade_max' name='filter_idade_max' class='form-control' min='0' max='100' value='{$filter_idade_max}'>
                                    </div>
                                    <div class='col-md-2'>
                                        <label for='filter_aniversario_date' class='form-label'>Data Aniversário:</label>
                                        <input type='date' id='filter_aniversario_date' name='filter_aniversario_date' class='form-control' value='{$filter_aniversario_date}'>
                                    </div>
                                    <div class='col-md-2'>
                                        <button type='submit' class='btn btn-primary me-2'>Filtrar</button>
                                        <a href='?p=alunos' class='btn btn-outline-secondary'>
                                            <i class='fas fa-eraser'></i>
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class='row'>
                <div class='col-12'>
                    <div class='card shadow-sm border-0'>
                        <div class='card-header bg_azul text-white d-flex justify-content-between align-items-center'>
                            <h5 class='mb-0 beje'>Lista de Alunos</h5>
                            <span class='badge bg-light text-dark'>Página {$page} de {$total_pages} ({$total_students} alunos)</span>
                        </div>
                        <div class='card-body p-0'>
                            <div class='table-responsive'>
                                <table class='alunos table table-striped'>
                                    <thead class='bg_azul'>
                                        <th>ID</th>
                                        <th>Foto</th>
                                        <th>Nome</th>
                                        <th>Assiduidade</th>
                                        <th>Aniversário</th>
                                        <th>Idade</th>
                                        <th>Email</th>
                                        <th>Contacto</th>
                                        <th>Mensalidade</th>
                                        <th>Certificado</th>
                                    </thead>
                                    <tbody>
                                        {$tbody}
                                    </tbody>
                                </table>
                            </div>
                            {$pagination}
                            <div class='d-flex justify-content-between align-items-end mb-4 mt-4 px-3'>
                                <div>
                                    <!-- <button class='btn btn-success btn-lg me-3' onclick='downloadAllStudentsCertificates()'
                                            title='Download de todos os certificados dos alunos na página atual'>
                                        <i class='fas fa-download me-2'></i>
                                        Download Todos os Certificados
                                    </button> -->
                                    <button class='btn btn-primary btn-lg' onclick='exportStudentsList()'
                                            title='Exportar lista dos alunos visíveis para Excel'>
                                        <i class='fas fa-file-excel me-2'></i>
                                        Exportar Lista de Alunos
                                    </button>
                                    <small class='d-block text-muted mt-1'>
                                        <i class='fas fa-info-circle me-1'></i>
                                        Exporta dados dos alunos visíveis
                                    </small>
                                </div>
                                <div class='text-end'>
                                    <h5>Alunos: " .
            count($page_students) .
            " (Total: {$total_students})</h5>
                                    <h5>Página - Total Mensalidades: " .
            number_format($page_total_mensalidades, 0) .
            "€</h5>
                                    <h5>Página - Total S/IVA: " .
            number_format($page_total_mensalidades / 1.23, 0) .
            "€</h5>
                                    <hr class='my-2'>
                                    <h5>Geral - Total Mensalidades: " .
            number_format($overall_total_mensalidades, 0) .
            "€</h5>
                                    <h5>Geral - Total S/IVA: " .
            number_format($overall_total_mensalidades / 1.23, 0) .
            "€</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        ";

        return $filter_interface;
    }

    function professoresLista()
    {
        $result = $this->emotionModel->professores();

        $total = $a = 0;
        $tbody = "";
        while ($row = $result->fetch_array()) {
            extract($row);

            $idade = $this->idade($data_nascimento);

            $aniversario = date("M d", strtotime($data_nascimento));

            $ativo = "";
            if (date("m-d", strtotime($data_nascimento)) == date("m-d")) {
                $aniversario = "<span class='fw-bolder bordeaux'>{$aniversario} ANIVERSÁRIO!</span>";
                $ativo = "ativo";
            }

            $foto = $this->fotoProfessor($id);

            // Prepare additional info
            $info_adicional = "";
            if (isset($alcunha) && !empty($alcunha) && $alcunha != $nome) {
                $info_adicional .= "<small class='text-muted d-block'><i class='fas fa-user'></i> {$alcunha}</small>";
            }
            if (isset($telemovel) && !empty($telemovel)) {
                $info_adicional .= "<small class='text-muted d-block'><i class='fas fa-phone'></i> {$telemovel}</small>";
            }
            if (isset($email) && !empty($email)) {
                $info_adicional .= "<small class='text-muted d-block'><i class='fas fa-envelope'></i> {$email}</small>";
            }
            if (isset($morada) && !empty($morada)) {
                $info_adicional .= "<small class='text-muted d-block'><i class='fas fa-map-marker-alt'></i> {$morada}</small>";
            }
            if (isset($nif) && !empty($nif)) {
                $info_adicional .= "<small class='text-muted d-block'><i class='fas fa-id-card'></i> NIF: {$nif}</small>";
            }

            if (isset($observacoes) && !empty($observacoes)) {
                $info_adicional .= "<small class='text-muted d-block'><i class='fas fa-comment'></i> {$observacoes}</small>";
            }

            $tbody .= "
                <tr class='{$ativo}'>
                    <td>{$id}</td>
                    <td>{$foto}</td>
                    <td><a href='?p=professor&id_professores={$id}'>{$nome}</a></td>
                    <td>{$aniversario}</td>
                    <td>{$idade}</td>
                    <td>
                        {$info_adicional}
                    </td>
                </tr>
            ";

            $a++;
        }

        return "
            <table class='professores table table-striped'>
                <thead>
                    <th>ID</th>
                    <th>Foto</th>
                    <th>Nome</th>
                    <th>Aniversário</th>
                    <th>Idade</th>
                    <th>Info</th>
                </thead>
                <tbody>
                    {$tbody}
                </tbody>
            </table>
        ";
    }

    function horarios()
    {
        if (!isset($id_horarios)) {
            $id_horarios = $this->horarioAtual();
        }

        $result = $this->emotionModel->horarios($id_horarios);

        return $result;
    }

    function horarioAtual()
    {
        return 3;
    }

    function diasSemana($dia)
    {
        $dias = [
            "segunda" => "2.ª Feira",
            "terca" => "3.ª Feira",
            "quarta" => "4.ª Feira",
            "quinta" => "5.ª Feira",
            "sexta" => "6.ª Feira",
            "sabado" => "Sábado" /*,
             'domingo' => 'Domingo'*/,
        ];

        if ($dia) {
            return $dias[$dia];
        }

        return $dias;
    }

    function aulas($id_horarios, $dia, $id_professores)
    {
        $result = $this->emotionModel->aulas(
            $id_horarios,
            $dia,
            $id_professores,
        );

        return $result;
    }

    function aulasProfessores(
        $id,
        $id_professores,
        $id_modalidades,
        $data_inicio,
        $data_fim,
    ) {
        $result = $this->emotionModel->aulasProfessores(
            $id,
            $id_professores,
            $id_modalidades,
            $data_inicio,
            $data_fim,
        );

        return $result;
    }

    function aluno($id_alunos)
    {
        if (!$id_alunos) {
            return true;
        }

        $result = $this->emotionModel->aluno($id_alunos);

        $row = $this->safeExtract($result);
        if (!$row) {
            return false;
        }

        extract($row);

        $idade = $this->idade($data_nascimento);

        $aluno["nome"] = "$nome ({$idade} anos)";
        $aluno["foto"] = $this->fotoAluno($id);

        return $aluno;
    }

    function professor($id_professores)
    {
        if (!$id_professores) {
            return true;
        }

        $result = $this->emotionModel->professor($id_professores);

        $row = $this->safeExtract($result);
        if (!$row) {
            return false;
        }

        extract($row);

        $idade = $this->idade($data_nascimento);

        $professor["nome"] = "$nome ({$idade} anos)";
        $professor["foto"] = $this->fotoProfessor($id);

        return $professor;
    }

    function perfilAluno($id_alunos)
    {
        $result = $this->emotionModel->aluno($id_alunos);
        $row = $result->fetch_array();
        extract($row);

        $aluno = $this->aluno($id_alunos);

        $contacto = $telemovel;
        if ($email_ee) {
            $email = $email_ee;
        }
        if ($telemovel_ee) {
            $contacto = "{$telemovel_ee} ({$nome_ee})";
        }

        #Presenças
        $presencas = "";
        $total_presente = $total_ausente = 0;
        $result = $this->emotionModel->presencasAluno($id_alunos);
        while ($row = $result->fetch_array()) {
            extract($row);

            $day = date("N", strtotime($data));
            $dia = $this->dias_semana[$day];
            $d = substr($this->diasSemana($dia), 0, 4);
            $dt = date("Y-m-d", strtotime($data));

            if ($presente == 1) {
                $presente =
                    "<span class='fw-bold text-success'>Presente</span>";
                $total_presente++;
                $modalidades[$modalidade]["presente"]++;
            } else {
                $presente = "<span class='fw-bold text-danger'>Faltou</span>";
                $total_ausente++;
                $modalidades[$modalidade]["ausente"]++;
            }

            $modalidades[$modalidade]["total"]++;

            $assiduidade = $this->assiduidade(
                $total_presente,
                $total_ausente,
                "",
            );
            $total_aulas = $total_presente + $total_ausente;

            $presencas .= "
            <tr>
                <td><a href='?p=aula&id={$id_aulas_professores}&id_aulas={$id_aulas}&data={$dt}'>{$data}</a> ({$d})</td>
                <td>{$modalidade}</td>
                <td>{$professor}</td>
                <td>{$horario}</td>
                <td>{$presente}</td>
            </tr>
        ";
        }
        #/Presenças

        #Assiduidade
        $ass_modalidades = "";
        foreach ($modalidades as $k => $m) {
            $ass = $this->assiduidade($m["presente"], $m["ausente"], "");

            $ass_modalidades .= "
            <div class='col-12 p-3 pb-2'>
                <div><b>{$k}</b> ({$m["presente"]}/{$m["total"]})</div>
                <div>{$ass[1]}</div>
            </div>
        ";
        }
        #/Assiduidade

        return "
        <div class='row'>
            <div class='col-1'>
                {$aluno["foto"]}
                <input type='hidden' id='id_alunos' value='{$id_alunos}' />
            </div>
            <div class='col-5'>
                <h1>{$aluno["nome"]}</h1>
                <p>{$contacto}</p>
                <p>{$email}</p>
            </div>
            <div class='col-6'>
                <h4>Assiduidade Geral: <span class='assiduidade'>{$assiduidade[0]}%</span> ({$total_presente}/{$total_aulas})</h4>
                <p>{$assiduidade[1]}</p>
            </div>
        </div>

        <hr>

        <ul class='nav nav-tabs' id='myTab' role='tablist'>
            <li class='nav-item' role='presentation'>
                <button class='nav-link active' id='horario-tab' data-bs-toggle='tab' data-bs-target='#horario' type='button' role='tab' aria-controls='horario' aria-selected='true'>Horário</button>
            </li>
            <li class='nav-item' role='presentation'>
                <button class='nav-link' id='presencas-tab' data-bs-toggle='tab' data-bs-target='#presencas' type='button' role='tab' aria-controls='presencas' aria-selected='false'>Presenças</button>
            </li>
            <li class='nav-item' role='presentation'>
                <button class='nav-link' id='assiduidade-tab' data-bs-toggle='tab' data-bs-target='#assiduidade' type='button' role='tab' aria-controls='assiduidade' aria-selected='false'>Assiduidade</button>
            </li>
        </ul>
        <div class='tab-content' id='myTabContent'>

            <div class='tab-pane show active' id='horario' role='tabpanel' aria-labelledby='horario-tab'>
                " .
            $this->horario() .
            "
            </div>

            <div class='tab-pane' id='presencas' role='tabpanel' aria-labelledby='presencas-tab'>
                <div class='table-responsive mt-4'>
                    <table class='table table-hover table-striped bg_beje'>
                        <thead class='bg_azul text-white'>
                            <th>Data</th>
                            <th>Modalidade</th>
                            <th>Professor</th>
                            <th>Horário</th>
                            <th>Estado</th>
                        </thead>
                        <tbody>
                            {$presencas}
                        </tbody>
                    </table>
                </div>
            </div>

            <div class='tab-pane' id='assiduidade' role='tabpanel' aria-labelledby='assiduidade-tab'>
                <div class='row'>
                    {$ass_modalidades}
                </div>
            </div>

        </div>
    ";
    }

    function perfilProfessor($id_professores)
    {
        $result = $this->emotionModel->professor($id_professores);
        $professor_row = $result->fetch_array();
        if (!$professor_row) {
            return "<div class='alert alert-danger mt-4'>Professor não encontrado ou inativo.</div>";
        }
        extract($professor_row);

        $professor_data = $this->professor($id_professores); // Evitar sobrescrever $professor_row
        $contacto = $telemovel;
        $nome_professor =
            isset($alcunha) && !empty($alcunha) ? $alcunha : $nome;
        $localidade = isset($morada) && !empty($morada) ? $morada : "N/A";
        $idade = $this->idade($data_nascimento);

        # Estatísticas de presenças
        $total_presente = $total_ausente = 0;
        $result_stats = $this->emotionModel->presencasProfessor(
            $id_professores,
        );
        if ($result_stats && $result_stats->num_rows > 0) {
            $stats = $result_stats->fetch_array();
            $total_presente = $stats["presentes"] ?? 0;
            $total_ausente = $stats["ausentes"] ?? 0;
        }
        $assiduidade_geral = $this->assiduidade(
            $total_presente,
            $total_ausente,
            "",
        );
        $total_aulas = $total_presente + $total_ausente;

        # Assiduidade por modalidade
        $ass_modalidades = "";
        $result_mod = $this->emotionModel->assiduidadeModalidadesProfessor(
            $id_professores,
        );
        if ($result_mod) {
            while ($row_mod = $result_mod->fetch_array()) {
                extract($row_mod);
                $ass = $this->assiduidade($presentes, $ausentes, "");
                $ass_modalidades .= "
                    <div class='col-12 p-3 pb-2'>
                        <div><b>{$nome}</b> ({$presentes}/{$total})</div>
                        <div>{$ass[1]}</div>
                    </div>
                ";
            }
        }

        # Horário do professor
        $horario_professor = $this->horarioProfessor($id_professores);

        # Aulas recentes (usar tabela igual ao aluno)
        $aulas_recentes = "";
        $result_aulas = $this->aulasProfessores(
            0,
            $id_professores,
            0,
            date("Y-m-d", strtotime("-30 days")),
            date("Y-m-d"),
        );
        if ($result_aulas && $result_aulas->num_rows > 0) {
            while ($row_aula = $result_aulas->fetch_array()) {
                extract($row_aula);
                $data_show = date("d-m-Y", strtotime($data));
                $aulas_recentes .=
                    "
                    <tr>
                        <td>{$data_show}</td>
                        <td>{$modalidade}</td>
                        <td>{$horario}</td>
                        <td>{$presentes}</td>
                        <td>{$ausentes}</td>
                        <td>" .
                    $this->assiduidade($presentes, $ausentes, "")[1] .
                    "</td>
                    </tr>
                ";
            }
        } else {
            $aulas_recentes =
                "<tr><td colspan='6'>Nenhuma aula encontrada nos últimos 30 dias</td></tr>";
        }

        return "
            <div class='row'>
                <div class='col-1'>
                    " .
            ($professor_data["foto"]
                ? $professor_data["foto"]
                : "<img src='img/user-default.png' alt='Sem foto' class='foto' />") .
            "
                    <input type='hidden' id='id_professores' value='{$id_professores}' />
                </div>
                <div class='col-5'>
                    <h4>{$nome_professor} ({$idade} anos)</h4>
                    <h5>{$contacto}</h5>
                    <h6>{$localidade}</h6>
                </div>
                <div class='col-6 text-end'>
                    <p class='fw-bold text-success mb-1'>Presente: {$total_presente}</p>
                    <p class='fw-bold text-danger'>Ausente: {$total_ausente}</p>
                    <p class='fw-bold mb-1'>Assiduidade ({$total_aulas}):</p>
                    <div class='d-inline-flex'>
                        {$assiduidade_geral[1]}
                    </div>
                </div>
            </div>

            <ul class='nav nav-tabs mt-3' id='myTab' role='tablist'>
                <li class='nav-item' role='presentation'>
                    <button class='nav-link active' id='horario-tab' data-bs-toggle='tab' data-bs-target='#horario' type='button' role='tab' aria-controls='horario' aria-selected='true'>Horário</button>
                </li>
                <li class='nav-item' role='presentation'>
                    <button class='nav-link' id='aulas-tab' data-bs-toggle='tab' data-bs-target='#aulas' type='button' role='tab' aria-controls='aulas' aria-selected='false'>Aulas Recentes</button>
                </li>
                <li class='nav-item' role='presentation'>
                    <button class='nav-link' id='assiduidade-tab' data-bs-toggle='tab' data-bs-target='#assiduidade' type='button' role='tab' aria-controls='assiduidade' aria-selected='false'>Assiduidade por Modalidade</button>
                </li>
            </ul>
            <div class='tab-content'>
                <div class='tab-pane show active' id='horario' role='tabpanel' aria-labelledby='horario-tab'>
                    {$horario_professor}
                </div>
                <div class='tab-pane' id='aulas' role='tabpanel' aria-labelledby='aulas-tab'>
                    <div class='col-12 text-left p-3 bg_vermelho'>
                        <h5 class='d-inline-flex beje m-0'>Aulas Recentes</h5>
                    </div>
                    <table class='table form-table table-striped'>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Modalidade</th>
                                <th>Horário</th>
                                <th>Presentes</th>
                                <th>Ausentes</th>
                                <th>Assiduidade</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$aulas_recentes}
                        </tbody>
                    </table>
                </div>
                <div class='tab-pane' id='assiduidade' role='tabpanel' aria-labelledby='assiduidade-tab'>
                    <div class='col-12 text-left p-3 bg_vermelho'>
                        <h5 class='d-inline-flex beje m-0'>Assiduidade por Modalidade</h5>
                    </div>
                    <div class='row'>
                        {$ass_modalidades}
                    </div>
                </div>
            </div>
            <!-- Download All Certificates Button -->
            <div class='row mt-4'>
                <div class='col-12 text-center'>
                    <button type='button' class='btn btn-primary btn-lg' onclick='downloadAllCertificates({$id_alunos})'>
                        <i class='fas fa-download'></i> Baixar Todos os Certificados
                    </button>
                </div>
            </div>
        ";
    }

    function horario()
    {
        $semana = $this->diasSemana(0);

        $dia_nr = date("w");
        $dia_hoje = array_keys($semana)[$dia_nr - 1];
        $hora_agora = date("H:i");

        $d = 0;
        $thead = $sthead = $tbody = "";
        foreach ($semana as $key => $dia) {
            $thead .= "<td colspan='2'>{$dia}</td>";
            $sthead .=
                "<td class='p-2'>Estúdio 1</td><td class='p-2'>Estúdio 2</td>";

            $result = $this->aulas($this->horarioAtual(), $key, 0);
            while ($row = $result->fetch_array()) {
                extract($row);
                $inicio = date("H:i", strtotime($inicio));
                $fim = date("H:i", strtotime($fim));

                if (isset($descricao)) {
                    $descricao = " {$descricao}";
                }
                $turma[$id] = "{$modalidade}{$descricao}";
                if ($abreviatura) {
                    $modalidade = $abreviatura;
                }
                if ($alcunha) {
                    $professor = $alcunha;
                }

                $agora = "";
                if (
                    $dia_hoje == $key &&
                    (strtotime($hora_agora) >= strtotime($inicio) &&
                        strtotime($hora_agora) <= strtotime($fim))
                ) {
                    $agora = "agora";
                }
                if ($this->id_alunos) {
                    $agora = "";
                }

                $data = "
                    <b class='{$agora}'>{$modalidade}{$descricao}</b><br/>
                    {$inicio} - {$fim}<br/>
                    <span class='small'>{$professor}</span>
                ";

                if ($inicio == "10:00") {
                    $aulas["16:30"][$dia][$estudio] = [
                        "desc" => $data,
                        "id" => $id,
                    ];
                } elseif ($inicio == "11:00") {
                    $aulas["17:30"][$dia][$estudio] = [
                        "desc" => $data,
                        "id" => $id,
                    ];
                } elseif ($inicio == "12:00") {
                    $aulas["18:30"][$dia][$estudio] = [
                        "desc" => $data,
                        "id" => $id,
                    ];
                } else {
                    $aulas[$inicio][$dia][$estudio] = [
                        "desc" => $data,
                        "id" => $id,
                    ];
                    $inicios[$inicio] = $inicio;
                }
            }
        }

        sort($inicios);

        foreach ($inicios as $inicio) {
            $tbody .= "<tr>";
            foreach ($semana as $key => $dia) {
                for ($e = 1; $e <= 2; $e++) {
                    if (isset($aulas[$inicio][$dia][$e]["id"])) {
                        $id_aulas = $aulas[$inicio][$dia][$e]["id"];
                        $desc = $aulas[$inicio][$dia][$e]["desc"];

                        #Alunos
                        $ativo = "";
                        $result = $this->emotionModel->alunosAulas(
                            $this->id_alunos,
                            $id_aulas,
                            $key,
                            $e,
                        );
                        if ($result->num_rows) {
                            $ativo = "ativo";
                        }
                        #/Alunos

                        #Total
                        $result = $this->emotionModel->alunosTurma(
                            $id_aulas,
                            $key,
                        );
                        $total = $result->num_rows;
                        #/Total

                        $titulo = "{$turma[$id_aulas]} ({$dia})";

                        if (
                            $dia_hoje == $key &&
                            (strtotime($hora_agora) >= strtotime($inicio) &&
                                strtotime($hora_agora) <= strtotime($fim))
                        ) {
                            $agora = "vermelho";
                        }

                        $tbody .= "<td class='link {$ativo} {$agora}' data-dia='{$key}' data-id_aulas='{$id_aulas}' data-turma='{$turma[$id_aulas]}' data-titulo='{$titulo}'>{$desc} <span class='small'>[<span class='total_alunos'>{$total}</span>]</span></td>";
                    } else {
                        $tbody .= "<td></td>";
                    }
                }
            }

            $tbody .= "</tr>";
        }

        $titulo = $this->horarios()->fetch_array()["descricao"];

        //echo "<h1 class='titulo text-uppercase'>{$titulo}</h1>";

        if (isset($aluno)) {
            $aluno = $this->aluno($this->id_alunos);

            echo "<h2>{$aluno["nome"]}</h2>";

            if (isset($aluno["foto"])) {
                echo "<p>{$aluno["foto"]}</p>";
            }
        }

        return "
        <table class='horario text-center mb-3'>
            <thead class='bg_beje azul'>
                <tr class='h2 text-center'>
                    {$thead}
                </tr>
                <tr class='blue text-uppercase'>
                    {$sthead}
                </tr>
            </thead>
            <tbody>
                {$tbody}
            </tbody>
        </table>

        <h4>Mensalidade: <span class='mensalidade'>" .
            $this->mensalidade($this->id_alunos) .
            "</span>€</h4>
        ";
    }

    function aulasLista($data_inicio, $data_fim)
    {
        if (!$data_inicio) {
            $data_inicio = $this->data_inicio;
            $data_fim = $this->data_fim;
        }

        $dt = date("Y-m-d", strtotime($data_inicio));

        $a = $total = $total_presentes = $total_ausentes = $total_assiduidade = $count_assiduidade = $ass = 0;
        $tbody = "";
        $result = $this->aulasProfessores(
            0,
            $this->id_professores,
            $this->id_modalidades,
            $data_inicio,
            $data_fim,
        );
        while ($row = $result->fetch_array()) {
            extract($row);

            /*
            #Interrupção
            $int = $this->emotionModel->interrupcao($dt)->fetch_row();
            if($int[0]){continue;}
            #/Interrupção
            */

            $data_show = date("d-m-Y", strtotime($data));
            $d = substr($this->diasSemana($dia), 0, 4);

            $total += $valor;
            $a++;

            #Assistência
            if ($presentes) {
                $count_assiduidade++;
            }
            $assiduidade = $this->assiduidade($presentes, $ausentes, "");
            #/Assistência

            $tbody .= "
                <tr style='cursor: pointer;' onclick=\"window.location.href='?p=aula&id={$id}&id_aulas={$id_aulas}&data={$data}'\">
                    <td><strong>{$data_show} ({$d})</strong></td>
                    <td><strong>{$modalidade}</strong></td>
                    <td>{$professor}</td>
                    <td><span class='badge bg-secondary'>{$horario}</span></td>
                    <td><span class='badge bg-success'>{$presentes}</span></td>
                    <td><span class='badge bg-danger'>{$ausentes}</span></td>
                    <td>{$assiduidade[1]}</td>
                </tr>
            ";

            /*
            #Contabilização
            $valor = number_format($valor, 2, ',', '');
            $tbody .=
            "
                <tr>
                    <td>{$data_show} ({$d})</td>
                    <td>{$modalidade}</td>
                    <td>{$horario}</td>
                    <td>1</td>
                    <td class=''>{$valor}€</td>
                </tr>
            ";
            #/Contabilização
            */

            $total_presentes += $presentes;
            $total_ausentes += $ausentes;
            $total_assiduidade += $assiduidade[0];
        }

        $ass = 0;
        $total = number_format($total, 2, ",", "");

        // Prevent division by zero
        if ($count_assiduidade > 0) {
            $ass = number_format(
                $total_assiduidade / $count_assiduidade,
                0,
                ",",
                "",
            );
        }

        if ($ass < 1) {
            $ass = 0;
        }

        $text_med = "text-danger";
        if ($ass >= $mid) {
            $text_med = "text-warning";
        }
        if ($ass >= $high) {
            $text_med = "text-success";
        }

        $media_ausentes = 0;
        if ($total_ausentes && $a > 0) {
            $media_ausentes = ceil($total_ausentes / $a);
        }

        $tbody .= "
            <tr class='fw-bold table-info'>
                <td><strong>AULAS</strong></td>
                <td><strong>{$a}</strong></td>
                <td></td>
                <td></td>
                <td><span class='badge bg-success'>{$total_presentes}</span></td>
                <td><span class='badge bg-danger'>{$total_ausentes}</span> <small>(Média: {$media_ausentes})</small></td>
                <td><span class='{$text_med} fw-bold'>Média: {$ass}%</span></td>
            </tr>
        ";

        return "
            <div class='table-responsive mt-4'>
                <table class='table table-hover table-striped bg_beje'>
                    <thead class='bg_azul text-white'>
                        <tr>
                            <th><i class='fas fa-calendar me-1'></i>Data</th>
                            <th><i class='fas fa-music me-1'></i>Modalidade</th>
                            <th><i class='fas fa-user me-1'></i>Professor</th>
                            <th><i class='fas fa-clock me-1'></i>Horário</th>
                            <th><i class='fas fa-check me-1'></i>Presentes</th>
                            <th><i class='fas fa-times me-1'></i>Ausentes</th>
                            <th><i class='fas fa-chart-line me-1'></i>Assiduidade</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$tbody}
                    </tbody>
                </table>
            </div>
        ";

        /*
        #Contabilização
        return
        "
            <table class='table form-table'>
                <thead>
                    <th>Data</th>
                    <th>Modalidade</th>
                    <th>Horário</th>
                    <th>Duração</th>
                    <th>Valor</th>
                </thead>
                <tbody>
                    {$tbody}
                </tbody>
            </table>
        ";
        #/Contabilização
        */
    }

    function assiduidade($presentes, $ausentes, $assiduidade)
    {
        #Config Limites
        $mid = 50;
        $high = 70;
        #/Config Limites

        #Assiduidade
        if (!$assiduidade) {
            // Ensure we have valid numeric values
            $presentes = (int) $presentes;
            $ausentes = (int) $ausentes;
            $total = $presentes + $ausentes;

            if ($presentes > 0 && $total > 0) {
                $assiduidade = round(($presentes * 100) / $total, 0);
            } else {
                $assiduidade = 0;
            }
        }
        #/Assiduidade

        // Ensure assiduidade is a valid number
        $assiduidade = (int) $assiduidade;

        $bg_bar = "bg-danger";
        if ($assiduidade >= $mid) {
            $bg_bar = "bg-warning";
        }
        if ($assiduidade >= $high) {
            $bg_bar = "bg-success";
        }

        $result[0] = $assiduidade;
        $result[1] = "
            <div class='progress'>
                <div class='progress-bar {$bg_bar}' role='progressbar' style='width: {$assiduidade}%;' aria-valuenow='{$assiduidade}' aria-valuemin='0' aria-valuemax='100'>{$assiduidade}%</div>
            </div>
        ";
        $result[2] = "<div class='dot {$bg_bar}' title='{$assiduidade}%'></div>";

        return $result;
    }

    function descontoFamilia($id_alunos)
    {
        $result = $this->emotionModel->aluno($id_alunos);
        $row = $result->fetch_array();

        if (isset($row["desconto_familia"])) {
            return $row["desconto_familia"];
        }
    }

    function mensalidade($id_alunos)
    {
        $result = $this->emotionModel->mensalidade(
            $id_alunos,
            $this->horarioAtual(),
        );
        $total = $result->num_rows;

        /*
        #Preçário 2023/2024 /#
        switch($total)
        {
            case 0: $mensalidade = 0; break;
            case 1: $mensalidade = 30; break;
            case 2: $mensalidade = 40; break;
            case 3: $mensalidade = 50; break;
            case 4: $mensalidade = 55; break;
            default: $mensalidade = 65; break;
        }
        */

        #Preçário 2024/2025 /#
        switch ($total) {
            case 0:
                $mensalidade = 0;
                break;
            case 1:
                $mensalidade = 35;
                break; /* 1 aula */
            case 2:
                $mensalidade = 45;
                break; /* 2 aulas */
            case 3:
                $mensalidade = 55;
                break; /* 3 aulas */
            case 4:
                $mensalidade = 60;
                break; /* 4 aulas */
            default:
                $mensalidade = 70;
                break; /* livre trânsito */
        }

        if ($total == 2) {
            $id_modalidades = 0;
            while ($row = $result->fetch_array()) {
                if (isset($id_modalidades)) {
                    if ($id_modalidades == $row["id_modalidades"]) {
                        $mensalidade = 40; /* 2 aulas mesma modalidade */
                    }
                }

                $id_modalidades = $row["id_modalidades"];
            }
        }

        if ($this->descontoFamilia($id_alunos)) {
            $mensalidade -= 2; #Desconto Família /#
        }

        #Emotion Dance Crew
        $result = $this->emotionModel->mensalidadeCrew($id_alunos);
        $total_crew = $result->num_rows;

        if (!$total && $total_crew) {
            $mensalidade += 35;
        } elseif ($total_crew) {
            $mensalidade += 20;
        }
        #/Emotion Dance Crew

        return number_format($mensalidade, 2);
    }

    function turmas()
    {
        $id_horarios = $this->horarioAtual();
        $semana = $this->diasSemana(0);
        $aulas_lista = []; // Inicializar array
        $id_aulas = null; // Inicializar variável
        $dia_form = null; // Inicializar variável

        if (isset($_GET["id_aulas"])) {
            $id_aulas = $_GET["id_aulas"];
        }

        if (isset($_GET["dia"])) {
            $dia_form = $_GET["dia"];
        }

        foreach ($semana as $key => $dia) {
            $result = $this->emotionModel->aulas($id_horarios, $key, 0);
            while ($row = $result->fetch_array()) {
                extract($row);

                $inicio = date("H:i", strtotime($inicio));
                if ($descricao) {
                    $descricao = " {$descricao}";
                }
                if ($professor) {
                    $professor = "[{$professor}]";
                }

                $aulas_lista[$key][
                    $id
                ] = "{$inicio} {$modalidade}{$descricao} (E{$estudio}) {$professor}";
            }
        }

        $data = date("Y-m-d");
        $day = date("N", strtotime($data));

        $options = "<option>Turmas</option>";
        if (isset($aulas_lista) && is_array($aulas_lista)) {
            foreach ($aulas_lista as $dia => $aula) {
                $options .= "<option disabled='disabled'>{$semana[$dia]}</option>";
                foreach ($aulas_lista[$dia] as $k => $a) {
                    $selected = "";
                    if ($id_aulas == $k && $dia_form == $dia) {
                        $selected = "selected='selected'";
                    }

                    $i = "";
                    if ($dia == $this->dias_semana[$day]) {
                        $i = "‣ ";
                    }

                    $options .= "<option value='{$k}' data-dia='{$dia}' {$selected}>{$i}{$a}</option>";
                }
            }
        }

        $readonly = "readonly='readonly'";
        if ($id_aulas) {
            $readonly = "";
        }

        if (isset($_GET["data"])) {
            $data = $_GET["data"];
        }

        // Adicionar atalhos para aulas do dia atual apenas se não estivermos a ver presenças específicas
        $atalhos_aulas = "";
        if (!isset($_GET["id_aulas"]) || !$_GET["id_aulas"]) {
            $atalhos_aulas = $this->atalhosDiaAtual();
        }

        return "
            <form method='get' name='turmas'>
                <input type='hidden' name='p' value='turmas' />
                <div class='input-group'>
                    <select name='id_aulas' class='form-select mb-3'>{$options}</select>
                </div>
                <input type='hidden' name='dia' value='' />
                <div class='input-group mb-3'>
                    <input id='data_turmas' class='form-control' name='data' type='date' value='{$data}' {$readonly} />
                </div>
            </form>
            {$atalhos_aulas}
        ";
    }

    function atalhosDiaAtual()
    {
        try {
            $data_atual = date("Y-m-d");
            $data_show = date("d-m-Y", strtotime($data_atual));

            // Obter aulas do dia atual
            $result = $this->aulasProfessores(
                0,
                0,
                0,
                $data_atual,
                $data_atual,
            );

            if (!$result || $result->num_rows == 0) {
                return "<div class='alert alert-info mt-4'>
                    <i class='fas fa-info-circle me-2'></i>
                    <strong>Não há aulas agendadas para hoje ({$data_show}).</strong>
                </div>";
            }

            $cards = "";

            while ($row = $result->fetch_array()) {
                extract($row);

                // Garantir que temos valores
                $id_professores = isset($id_professores) ? $id_professores : 0;
                $professor = isset($professor) ? $professor : "Professor";
                $modalidade = isset($modalidade) ? $modalidade : "Modalidade";
                $horario = isset($horario) ? $horario : "Horário";

                // Calcular assiduidade
                $presentes = isset($presentes) ? $presentes : 0;
                $ausentes = isset($ausentes) ? $ausentes : 0;
                $assiduidade = $this->assiduidade($presentes, $ausentes, "");

                // SEMPRE criar uma imagem ou círculo
                $foto_professor = "";
                if ($id_professores > 0) {
                    $caminho_foto = "professores/{$id_professores}.jpg";

                    if (file_exists($caminho_foto)) {
                        $foto_professor =
                            "<img src='{$caminho_foto}?v=" .
                            time() .
                            "' alt='Professor {$professor}' class='aulas-hoje-foto-prof' style='width: 50px; height: 50px; border-radius: 50%; object-fit: cover; margin-right: 10px; border: 2px solid #fff; background: #ddd;' />";
                    } else {
                        // Círculo com primeira letra do nome do professor
                        $primeira_letra = strtoupper(substr($professor, 0, 1));
                        $foto_professor = "<div style='width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(45deg, #007bff, #0056b3); color: white; display: flex; align-items: center; justify-content: center; margin-right: 10px; font-weight: bold; font-size: 18px; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);'>{$primeira_letra}</div>";
                    }
                } else {
                    // Se não tiver ID do professor, usar círculo genérico
                    $primeira_letra = strtoupper(substr($professor, 0, 1));
                    $foto_professor = "<div style='width: 50px; height: 50px; border-radius: 50%; background: #6c757d; color: white; display: flex; align-items: center; justify-content: center; margin-right: 10px; font-weight: bold; font-size: 16px;'>{$primeira_letra}</div>";
                }

                $classe_status = "";
                $selo_terminada = "";
                $hora_atual = date("H:i");
                $agora = strtotime($hora_atual);
                $inicio_aula = isset($inicio) ? strtotime($inicio) : 0;
                $fim_aula = isset($fim) ? strtotime($fim) : 0;
                if ($fim && $fim_aula < $agora) {
                    $classe_status = "aula-terminada";
                    $selo_terminada =
                        "<span class='badge badge-terminada'>Terminada</span>";
                } elseif (
                    $inicio &&
                    $inicio_aula <= $agora &&
                    $fim &&
                    $fim_aula >= $agora
                ) {
                    $classe_status = "aula-agora";
                } else {
                    $classe_status = "aula-futura";
                }
                $cards .=
                    "\n" .
                    "<div class='col-md-4 col-lg-3 col-xl-2 mb-3'>\n" .
                    "    <div class='d-flex align-items-center' style='min-height: 120px;'>\n" .
                    "        <div style='flex-shrink: 0;'>\n" .
                    "            {$foto_professor}\n" .
                    "        </div>\n" .
                    "        <div class='card shadow-sm bg_azul {$classe_status}' style='cursor: pointer; transition: transform 0.2s; min-height: 100px; flex-grow: 1; position: relative;'\n" .
                    "             onclick=\"window.location.href='?p=turmas&id_aulas={$id_aulas}&dia={$dia}&data={$data}'\"\n" .
                    "             onmouseover='this.style.transform=\"scale(1.02)\"'\n" .
                    "             onmouseout='this.style.transform=\"scale(1)\"'>\n" .
                    "            {$selo_terminada}\n" .
                    "            <div class='text-white p-3 d-flex flex-column h-100'>\n" .
                    "                <div class='text-center mb-2'>\n" .
                    "                    <div class='fw-bold' style='line-height: 1.2; word-wrap: break-word; font-size: 0.9rem;'>{$modalidade}</div>\n" .
                    "                </div>\n" .
                    "                <div class='text-center mt-auto'>\n" .
                    "                    <span class='badge bg-light text-dark' style='font-size: 0.75rem;'>{$horario}</span>\n" .
                    "                </div>\n" .
                    "            </div>\n" .
                    "        </div>\n" .
                    "    </div>\n" .
                    "</div>\n";
            }

            return "
                <div class='mt-4'>
                    <div class='d-flex align-items-center justify-content-between mb-3'>
                        <h5 class='mb-0'>
                            <i class='fas fa-calendar-day me-2 text-primary'></i>
                            Aulas de Hoje ({$data_show})
                        </h5>
                        <small class='text-muted'>Clique numa aula para gerir presenças</small>
                    </div>

                    <div class='row'>
                        {$cards}
                    </div>
                </div>
            ";
        } catch (Exception $e) {
            return "<div class='alert alert-danger mt-3'>
                <i class='fas fa-exclamation-triangle me-2'></i>
                <strong>Erro ao carregar aulas:</strong> " .
                $e->getMessage() .
                "
            </div>";
        }
    }

    function turma($id_aulas, $dia, $data)
    {
        // Garante que todas as aulas do dia/data tenham registro em aulas_professores
        $id_horarios = $this->horarioAtual();
        $weekday_num = date("N", strtotime($data));
        $weekday_str = $this->dias_semana[$weekday_num];
        $result_aulas = $this->emotionModel->aulas(
            $id_horarios,
            $weekday_str,
            0,
        );
        while ($row = $result_aulas->fetch_array()) {
            // Use variáveis locais para evitar sobrescrever $data
            $id_aula_loop = $row["id"];
            $id_prof_loop = $row["id_professores"];
            $inicio_loop = $row["inicio"];
            $fim_loop = $row["fim"];
            $valor_loop = $row["valor"];
            $exists = $this->emotionModel->aulaData($id_aula_loop, $data);
            if (!$exists) {
                $this->emotionModel->aulaProfessor(
                    $id_prof_loop,
                    $id_aula_loop,
                    $weekday_str,
                    $data,
                    $inicio_loop,
                    $fim_loop,
                    $valor_loop,
                );
            }
        }

        $a = 1;
        $alunos = "";

        // Buscar informações da aula para o cabeçalho (após garantir criação)
        $result_header = $this->emotionModel->aula($id_aulas, $data);
        $header_data = $result_header->fetch_array();
        if (!$header_data) {
            return "<p class='text-danger fw-bold'>Erro: Dados da aula não encontrados para id_aulas={$id_aulas}, data={$data}.</p>";
        }
        extract($header_data);

        // Buscar estatísticas da aula
        $result_stats = $this->aulasProfessores(
            $id_aulas,
            $id_professores,
            $id_modalidades,
            $data,
            $data,
        );
        $stats_data = $result_stats->fetch_array();
        if (!$stats_data) {
            $stats_data = ["presentes" => 0, "ausentes" => 0];
        }
        $assiduidade_header = $this->assiduidade(
            $stats_data["presentes"],
            $stats_data["ausentes"],
            "",
        );
        $total_presencas = $stats_data["presentes"] + $stats_data["ausentes"];

        // Formatar dados para exibição
        $data_show = date("d-m-Y", strtotime($data));
        $inicio_show = date("H:i", strtotime($inicio));
        $fim_show = date("H:i", strtotime($fim));
        $dia_show = $this->diasSemana($dia);

        // Informações do professor
        $foto_prof = $info_prof = "";
        if ($id_professores) {
            $foto_prof = "<img src='professores/{$id_professores}.jpg' alt='' class='w-100' />";
            $idade_prof = $this->idade($data_nascimento);
            $info_prof = "
                <h3>{$professor}</h3>
                <h5>{$telemovel}</h5>
                <h6>{$idade_prof} anos</h6>
            ";
        }

        $result = $this->emotionModel->alunosTurma($id_aulas, $dia);
        $alunos_count = $result->num_rows;
        if ($alunos_count === 0) {
            $result = $this->emotionModel->alunosTurma($id_aulas, "");
        }

        /*
        if ($alunos_count === 0) {
            // Forçar criação das ausências/presenças se a lista vier vazia (caso de aula movida de dia)
            if (method_exists($this, 'alunosTurmaPresencas')) {
                $this->alunosTurmaPresencas($id_aulas, $dia, $data);
            }
            $result = $this->emotionModel->alunosTurma($id_aulas, $dia);
        }
        */

        while ($row = $result->fetch_array()) {
            extract($row);

            // NÃO sobrescrever $data do cabeçalho!
            $data_inscricao_aluno = date("Y-m-d", strtotime($data_inscricao));
            if ($data_inscricao_aluno > $data) {
                continue;
            }

            $nome = $this->nomeAluno($nome, $alcunha);

            $ativo = $check = "";
            $presente = $this->presencaAluno($id_aulas, $id_alunos, $data);
            if ($presente == 1) {
                $ativo = "ativo";
                $check = "check";
            }

            $foto = $this->fotoAluno($id_alunos);
            $assiduidade = $this->assiduidade("", "", $assiduidade);

            $aniversario = $bg_aniv = "";
            if (date("m-d", strtotime($data_nascimento)) == date("m-d")) {
                $aniversario = "🥳";
                $bg_aniv = "bg-info";
            }

            $idade = $this->idade($data_nascimento);

            $alunos .= "
                <li>
                    <a href='#' data-id_alunos='$id_alunos' class='{$ativo}'>{$foto}</a>
                    <div class='details {$check} {$bg_aniv}'>
                        <b>{$a}. {$nome}</b> <span class='small'>{$idade}</span> {$aniversario} {$assiduidade[2]}
                    </div>
                </li>
            ";

            $a++;
        }

        // Retornar cabeçalho + lista de alunos
        return "
            <div class='container'>
                <div class='row bg_azul p-3 align-middle'>
                    <div class='col-8 text-left p-0'><h3 class='beje m-0'>{$modalidade}</h3></div>
                    <div class='col-4 text-right p-0 align-items-center justify-content-end beje'><h5 class='m-0'><b>{$data_show}</b> / {$dia_show} / {$inicio_show} > {$fim_show}</h5></div>
                </div>
                <div class='row pt-3 pl-0 pb-3'>
                    <div class='col-1 p-0'>
                        {$foto_prof}
                    </div>
                    <div class='col-5'>
                        {$info_prof}
                    </div>
                    <div class='col-6 text-end aula-stats-container'>
                        <div class='aula-stats'>
                            <p class='fw-bold text-success mb-1 stat-item' data-stat='presentes'>Presentes: <span class='stat-value'>{$stats_data["presentes"]}</span></p>
                            <p class='fw-bold text-danger stat-item' data-stat='ausentes'>Ausentes: <span class='stat-value'>{$stats_data["ausentes"]}</span></p>
                            <p class='fw-bold mb-1 stat-item' data-stat='total'>Assiduidade (<span class='stat-value'>{$total_presencas}</span>):</p>
                            <div class='d-inline-flex assiduidade-bar'>
                                {$assiduidade_header[1]}
                            </div>
                        </div>
                        <div class='aula-stats-loading d-none'>
                            <i class='fas fa-spinner fa-spin'></i>
                        </div>
                    </div>
                </div>
                <div class='row p-0'>
                    <div class='col-12 text-left p-3 bg_vermelho'>
                        <h5 class='beje m-0'>Presenças</h5>
                    </div>
                    <div class='col-12 p-0'>
                        <ul class='image-list-small mt-4'>
                            {$alunos}
                        </ul>
                    </div>
                </div>
            </div>
        ";
    }

    function nomeAluno($nome, $alcunha)
    {
        if (isset($alcunha)) {
            $nome = $alcunha;
            return $nome;
        }

        $apelido = "";
        $nome = explode(" ", $nome);
        if (isset($nome[1])) {
            $apelido = end($nome);
        }

        if ($nome[0] == "Maria" && strlen($nome[1]) > 2) {
            $nome = "{$nome[0]} {$nome[1]}";
        } elseif ($nome[0] == "Maria" && strlen($nome[1]) <= 2) {
            $nome = "{$nome[0]} {$nome[1]} {$nome[2]}";
        } elseif (isset($apelido)) {
            $nome = "{$nome[0]} {$apelido}";
        } else {
            $nome = "{$nome[0]}";
        }

        return $nome;
    }

    function nomeProfessor($nome, $alcunha)
    {
        if (isset($alcunha)) {
            $nome = $alcunha;
            return $nome;
        }

        $apelido = "";
        $nome = explode(" ", $nome);
        if (isset($nome[1])) {
            $apelido = end($nome);
        }

        if (isset($apelido)) {
            $nome = "{$nome[0]} {$apelido}";
        } else {
            $nome = "{$nome[0]}";
        }

        return $nome;
    }

    function alunosTurmaPresencas($id_aulas, $dia, $data)
    {
        $data_aula = $data;
        $result_alunos = $this->emotionModel->alunosTurma($id_aulas, $dia);
        while ($row = $result_alunos->fetch_array()) {
            extract($row);
            $this->presencaAluno($id_aulas, $id_alunos, $data_aula);
        }
    }

    function presencaAluno($id_aulas, $id_alunos, $data)
    {
        $result = $this->emotionModel->presencaAluno(
            $id_aulas,
            $id_alunos,
            $data,
        );
        if ($row = $result->fetch_array()) {
            if ($row["presente"] == 1) {
                return 1;
            } elseif ($row["presente"] == 0) {
                return 0;
            }
        } else {
            $horas = date("H:i:s");
            if ($this->aulaProfessor($id_aulas, $data)) {
                $this->emotionModel->createPresenca(
                    $id_alunos,
                    $id_aulas,
                    "",
                    0,
                    "{$data} {$horas}",
                );
            }

            return false;
        }
    }

    function aulaProfessor($id_aulas, $data)
    {
        if (isset($_GET["dia"])) {
            $dia = $_GET["dia"];
        }

        $data_form = $data;

        $result = $this->emotionModel->aulasProfessores(
            0,
            $this->id_professores,
            $this->id_modalidades,
            $data,
            $data,
        );
        if (!$result->num_rows) {
            #Interrupção
            $int = $this->emotionModel->interrupcao($data)->fetch_row();
            if ($int[0]) {
                return false;
            }
            #/Interrupção

            $result = $this->aulas($this->horarioAtual(), $dia, 0);
            while ($row = $result->fetch_array()) {
                extract($row);
                $this->emotionModel->aulaProfessor(
                    $id_professores,
                    $id,
                    $dia,
                    $data_form,
                    $inicio,
                    $fim,
                    $valor,
                );
            }

            return true;
        }

        return false;
    }

    function pedagogiaLista()
    {
        $result = $this->emotionModel->pedagogia();
        while ($row = $result->fetch_array()) {
            extract($row);

            $data_show = date("d-m-Y", strtotime($data));

            $aluno_falou = $aluno_mencionado = $tbody = "";
            $alunos = $this->emotionModel->alunosPedagogia($id);
            $falou = $mencionado = "";
            while ($aluno = $alunos->fetch_array()) {
                extract($aluno);

                $style = "style='width:auto; height:70px; margin-right:5px;'";

                if ($falou == 1) {
                    $aluno_falou .= "<img src='alunos/{$id_alunos}.jpg' alt='' title='{$aluno}' {$style} />";
                } else {
                    $aluno_mencionado .= "<img src='alunos/{$id_alunos}.jpg' alt='' title='{$aluno}' {$style} />";
                }
            }

            $foto = "";
            if (file_exists("pedagogia/{$id_pedagogia}.jpg")) {
                $foto = "<a class='btn btn-secondary btn-sm' data-toggle='collapse' href='#foto{$id_pedagogia}' role='button' aria-expanded='false' aria-controls='foto{$id_pedagogia}'>Foto</a>";
            }

            $tbody .= "
                <tr>
                    <td>{$resumo}</td>
                    <td>{$modalidade}</td>
                    <td>{$professor}</td>
                </tr>
                <tr>
                    <td colspan='3'><b>{$data_show}</b> {$foto}</td>
                </tr>
                <tr class='collapse' id='foto{$id_pedagogia}'>
                    <td colspan='3'>
                        <img src='pedagogia/{$id_pedagogia}.jpg' alt='' style='width: 100%' />
                    </td>
                </tr>
                <tr>
                    <td>{$aluno_falou}</td>
                    <td>{$aluno_mencionado}</td>
                    <td><img src='professores/{$id_professores}.jpg' alt='' {$style} /></td>
                </tr>
                <tr>
                    <td class='small'><p><b>Problema</b></p>{$problema}</td>
                    <td class='small'><p><b>Solução</b></p>{$solucao}</td>
                    <td class='small'><p><b>Feedback</b></p>{$feedback}</td>
                </tr>
            ";
        }
        $table = "
            <table class='pedagogia table table-striped'>
                <thead>
                    <th>Resumo</th>
                    <th>Modalidade</th>
                    <th>Professor</th>
                </thead>
                <tbody>
                    {$tbody}
                </tbody>
            </table>
        ";

        return $table;
    }

    function aula($id, $data)
    {
        $result = $this->emotionModel->aula($id, $data);

        $row = $this->safeExtract($result);
        if (!$row) {
            return "<div class='alert alert-danger'>Erro: Aula não encontrada ou ID inválido.</div>";
        }

        extract($row);

        #Presenças
        $tbody = $options = "";
        $presencas_aula = $this->emotionModel->presencasAula($id_aulas, $data);
        if ($presencas_aula->num_rows === 0) {
            // Forçar criação das ausências/presenças se a lista vier vazia (caso de aula movida de dia)
            if (method_exists($this, "alunosTurmaPresencas")) {
                $result_aula_prof = $this->emotionModel->aula($id_aulas, $data);
                $row_aula_prof = $result_aula_prof->fetch_array();
                $dia_aula = $row_aula_prof["dia"];
                $this->alunosTurmaPresencas($id_aulas, $dia_aula, $data);
            }
            $presencas_aula = $this->emotionModel->presencasAula(
                $id_aulas,
                $data,
            );
        }
        while ($aluno = $presencas_aula->fetch_array()) {
            $presenca = "<span class='text-danger'><b>Faltou</b></span>";
            $class = "text-danger";
            $presente_class = "btn-outline-success";
            $faltou_class = "btn-danger";

            if ($aluno["presente"]) {
                $presenca = "<span class='text-success'><b>Presente</b></span>";
                $class = "text-success";
                $presente_class = "btn-success";
                $faltou_class = "btn-outline-danger";
            }

            $foto = $this->fotoAluno($aluno["id"]);
            $assiduidade = $this->assiduidade("", "", $aluno["assiduidade"]);

            $tbody .= "
                <tr class='align-middle' style='line-height:50px;'>
                    <td>{$foto}</td>
                    <td>{$aluno["nome"]} {$assiduidade[2]}</td>
                    <td class='text-end'>
                        <a href='#' class='text-dark apagaPresenca' data-id_presencas='{$aluno["id_presencas"]}'><i class='fa-regular fa-trash-can'></i></a>
                    </td>
                    <td>
                        <div class='btn-group presence-buttons' role='group' data-id_presencas='{$aluno["id_presencas"]}'>
                            <button type='button' class='btn {$presente_class} btn-sm presence-btn' data-status='1' title='Marcar como Presente'>
                                <i class='fas fa-check me-1'></i>P
                            </button>
                            <button type='button' class='btn {$faltou_class} btn-sm presence-btn' data-status='0' title='Marcar como Faltou'>
                                <i class='fas fa-times me-1'></i>F
                            </button>
                        </div>
                    </td>
                    <td><input type='text' class='form-control' name='observacoes' value='{$aluno["observacoes"]}' data-id_presencas='{$aluno["id_presencas"]}' /></td>
                </tr>
            ";
        }
        #/Presenças

        #Aula
        $total_presencas = 0;
        $result_aula = $this->aulasProfessores(
            $id,
            $id_professores,
            $id_modalidades,
            $data,
            $data,
        );
        $row_aula = $result_aula->fetch_array();

        $assiduidade = $this->assiduidade(
            $row_aula["presentes"],
            $row_aula["ausentes"],
            "",
        );
        $total_presencas = $row_aula["presentes"] + $row_aula["ausentes"];
        #/Aula

        $link = "?p=turmas&id_aulas={$id_aulas}&dia={$dia}&data={$data}";

        $data = date("d-m-Y", strtotime($data));
        $inicio = date("H:i", strtotime($inicio));
        $fim = date("H:i", strtotime($fim));

        $dia = $this->diasSemana($dia);

        $idade = $this->idade($data_nascimento);

        if ($id_professores) {
            $foto_prof = "<img src='professores/{$id_professores}.jpg' alt='' class='w-100' />";
            $info_prof = "
                <h3>{$professor}</h3>
                <h5>{$telemovel}</h5>
                <h6>{$idade} anos</h6>
            ";
        }

        $result = "
            <div class='container'>
                <div class='row bg_azul p-3 align-middle'>
                    <div class='col-8 text-left p-0'><h3 class='beje m-0'>{$modalidade}</h3></div>
                    <div class='col-4 text-right p-0 align-items-center justify-content-end beje'><h5 class='m-0'><b>{$data}</b> / {$dia} / {$inicio} > {$fim}</h5></div>
                </div>
                <div class='row pt-3 pl-0 pb-3'>
                    <div class='col-1 p-0'>
                        {$foto_prof}
                    </div>
                    <div class='col-5'>
                        {$info_prof}
                    </div>
                    <div class='col-6 text-end aula-stats-container'>
                        <div class='aula-stats'>
                            <p class='fw-bold text-success mb-1 stat-item' data-stat='presentes'>Presentes: <span class='stat-value'>{$row_aula["presentes"]}</span></p>
                            <p class='fw-bold text-danger stat-item' data-stat='ausentes'>Ausentes: <span class='stat-value'>{$row_aula["ausentes"]}</span></p>
                            <p class='fw-bold mb-1 stat-item' data-stat='total'>Assiduidade (<span class='stat-value'>{$total_presencas}</span>):</p>
                            <div class='d-inline-flex assiduidade-bar'>
                                {$assiduidade[1]}
                            </div>
                        </div>
                        <div class='aula-stats-loading d-none'>
                            <i class='fas fa-spinner fa-spin'></i>
                        </div>
                    </div>
                </div>
                <div class='row p-0'>
                    <div class='col-12 text-left p-3 bg_vermelho'>
                        <div class='row'>
                            <div class='col'>
                                <a href='{$link}'><h5 class='d-inline-flex beje m-0'>Presenças</h5></a>
                            </div>
                            <div class='col d-flex align-items-center flex-row-reverse' style='text-align:right;'>
                                <a href='#' class='beje apagaPresencas text-end'><i class='fa-regular fa-trash-can'></i></a>
                            </div>
                        </div>
                    </div>
                    <div class='col-12 p-0'>
                        <table id='presencas' class='table table-striped'>
                            <thead class='d-none'>
                                <tr>
                                    <th>Foto</th>
                                    <th>Aluno</th>
                                    <th>Presenças</th>
                                </tr>
                            </thead>
                            <tbody>
                                {$tbody}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        ";

        return $result;
    }

    function eventos()
    {
        $tbody = "";
        $t = 0;
        $result = $this->emotionModel->eventos();

        if (!$result) {
            return "
                <div class='alert alert-danger' role='alert'>
                    <h4 class='alert-heading'>Erro de base de dados</h4>
                    <p>Não foi possível carregar os eventos. Verifique a conexão com a base de dados.</p>
                </div>
            ";
        }

        while ($row = $result->fetch_array()) {
            extract($row);

            // Handle missing fields with defaults
            $data = isset($data) ? $data : "N/A";
            $categoria = isset($categoria) ? $categoria : "N/A";
            $nome = isset($nome) ? $nome : "N/A";
            $local = isset($local) ? $local : "N/A";
            $localidade = isset($localidade) ? $localidade : "N/A";
            $professores = isset($professores) ? $professores : "0";
            $alunos = isset($alunos) ? $alunos : "0";
            $id = isset($id) ? $id : "0";

            // Add destaque status indicator with fallback
            $destaque_indicator = "";
            if (isset($destaque) && $destaque == 1) {
                $destaque_indicator =
                    "<span class='badge rounded-pill' style='background-color: #a92b4d; color: #feefdc; margin-right: 8px; font-size: 12px;'>★</span>";
            } else {
                $destaque_indicator =
                    "<span class='badge rounded-pill' style='background-color: #2c254b; color: #feefdc; margin-right: 8px; font-size: 12px;'>○</span>";
            }

            $tbody .= "
                <tr>
                    <td>{$data}</td>
                    <td>{$categoria}</td>
                    <td><a href='?p=evento&id_eventos={$id}' class='evento-link'>{$nome}</a></td>
                    <td>{$local}</td>
                    <td>{$localidade}</td>
                    <td>{$professores}</td>
                    <td>{$alunos}</td>
                    <td class='text-center'>{$destaque_indicator}</td>
                </tr>
            ";

            $t++;
        }

        if ($t == 0) {
            return "
                <div class='alert alert-info' role='alert'>
                    <h4 class='alert-heading'>Nenhum evento encontrado</h4>
                    <p>Não há eventos cadastrados no momento.</p>
                </div>
            ";
        }

        return "
            <table class='eventos table table-striped'>
                <thead>
                    <th>Data</th>
                    <th>Categoria</th>
                    <th>Nome</th>
                    <th>Local</th>
                    <th>Localidade</th>
                    <th>Professores</th>
                    <th>Alunos</th>
                    <th class='text-center'>Destaque</th>
                </thead>
                <tbody>
                    {$tbody}
                </tbody>
            </table>
            <div class='w-100 text-end'>
                <h5>Total: {$t}</h5>
            </div>
        ";
    }

    function horarioProfessor($id_professores)
    {
        $semana = $this->diasSemana(0);
        $dia_nr = date("w");
        $dia_hoje = array_keys($semana)[$dia_nr - 1];
        $hora_agora = date("H:i");
        $thead = $sthead = $tbody = "";
        $aulas = [];
        $inicios = [];
        $turma = [];

        // Buscar TODAS as aulas (id_professores = 0) para montar a grade completa
        foreach ($semana as $key => $dia) {
            $thead .= "<td colspan='2'>{$dia}</td>";
            $sthead .=
                "<td class='p-2'>Estúdio 1</td><td class='p-2'>Estúdio 2</td>";
            $result = $this->emotionModel->aulas(
                $this->horarioAtual(),
                $key,
                0,
            ); // 0 = todas as aulas
            while ($row = $result->fetch_array()) {
                $inicio = date("H:i", strtotime($row["inicio"]));
                $fim = date("H:i", strtotime($row["fim"]));
                $modalidade = $row["modalidade"];
                $professor = $row["professor"];
                $id_prof_aula = $row["id_professores"];
                $descricao = isset($row["descricao"])
                    ? " {$row["descricao"]}"
                    : "";
                $abreviatura = isset($row["abreviatura"])
                    ? $row["abreviatura"]
                    : "";
                $alcunha = isset($row["alcunha"]) ? $row["alcunha"] : "";
                if ($abreviatura) {
                    $modalidade = $abreviatura;
                }
                if ($alcunha) {
                    $professor = $alcunha;
                }
                $agora = "";
                if (
                    $dia_hoje == $key &&
                    (strtotime($hora_agora) >= strtotime($inicio) &&
                        strtotime($hora_agora) <= strtotime($fim))
                ) {
                    $agora = "agora";
                }
                $data = "<b class='{$agora}'>{$modalidade}{$descricao}</b><br/>{$inicio} - {$fim}<br/><span class='small'>{$professor}</span>";
                $estudio = $row["estudio"];
                $id_aulas = $row["id"];
                // Tratamento especial para sábado igual ao horario()
                if ($key == "sabado") {
                    if ($inicio == "10:00") {
                        $aulas["16:30"][$dia][$estudio] = [
                            "desc" => $data,
                            "id" => $id_aulas,
                            "id_professores" => $id_prof_aula,
                        ];
                    } elseif ($inicio == "11:00") {
                        $aulas["17:30"][$dia][$estudio] = [
                            "desc" => $data,
                            "id" => $id_aulas,
                            "id_professores" => $id_prof_aula,
                        ];
                    } elseif ($inicio == "12:00") {
                        $aulas["18:30"][$dia][$estudio] = [
                            "desc" => $data,
                            "id" => $id_aulas,
                            "id_professores" => $id_prof_aula,
                        ];
                    } else {
                        $aulas[$inicio][$dia][$estudio] = [
                            "desc" => $data,
                            "id" => $id_aulas,
                            "id_professores" => $id_prof_aula,
                        ];
                        $inicios[$inicio] = $inicio;
                    }
                } else {
                    $aulas[$inicio][$dia][$estudio] = [
                        "desc" => $data,
                        "id" => $id_aulas,
                        "id_professores" => $id_prof_aula,
                    ];
                    $inicios[$inicio] = $inicio;
                }
                $turma[$id_aulas] = "{$modalidade}{$descricao}";
            }
        }
        sort($inicios);
        foreach ($inicios as $inicio) {
            $tbody .= "<tr>";
            foreach ($semana as $key => $diaNome) {
                for ($e = 1; $e <= 2; $e++) {
                    if (isset($aulas[$inicio][$diaNome][$e]["id"])) {
                        $id_aulas = $aulas[$inicio][$diaNome][$e]["id"];
                        $desc = $aulas[$inicio][$diaNome][$e]["desc"];
                        $id_prof_aula =
                            $aulas[$inicio][$diaNome][$e]["id_professores"];
                        $titulo = isset($turma[$id_aulas])
                            ? $turma[$id_aulas] . " ({$diaNome})"
                            : "";
                        $classe =
                            $id_prof_aula == $id_professores ? "ativo" : "";
                        $tbody .= "<td class='{$classe}'>{$desc}</td>";
                    } else {
                        $tbody .= "<td></td>";
                    }
                }
            }
            $tbody .= "</tr>";
        }
        return "<table class='horario text-center mb-3'>
            <thead class='bg_beje azul'>
                <tr class='h2 text-center'>{$thead}</tr>
                <tr class='blue text-uppercase'>{$sthead}</tr>
            </thead>
            <tbody>{$tbody}</tbody>
        </table>";
    }

    /**
     * Calcula a média de assiduidade de um array de aulas, ignorando aulas com assiduidade 0%
     * @param array $aulas
     * @return int Média arredondada
     */
    public function mediaAssiduidadeAulas($aulas)
    {
        $soma = 0;
        $conta = 0;
        foreach ($aulas as $aula) {
            if (isset($aula["assiduidade"]) && $aula["assiduidade"] > 0) {
                $soma += $aula["assiduidade"];
                $conta++;
            }
        }
        return $conta > 0 ? round($soma / $conta) : 0;
    }

    function generateCertificate()
    {
        #Santiago 18/07 18:20
        if (!isset($_GET["id_aluno"])) {
            echo "Parâmetro id_aluno é obrigatório";
            return;
        }

        $id_aluno = (int) $_GET["id_aluno"];

        // Check if student exists
        $result = $this->emotionModel->mysqli->query("
            SELECT `nome` FROM `alunos` WHERE `id` = {$id_aluno} AND `ativo` = 1
        ");

        if ($result->num_rows === 0) {
            echo "Aluno não encontrado";
            return;
        }

        // Redirect to pdf.php for certificate generation
        header("Location: pdf.php?id_alunos={$id_aluno}&generate=1");
        exit();
    }

    function evento($id_eventos)
    {
        // Buscar dados do evento
        $result = $this->emotionModel->evento($id_eventos);
        $evento = $result->fetch_array();

        if (!$evento) {
            return "
                <div class='container'>
                    <div class='row bg_azul p-3 align-middle'>
                        <div class='col-8 text-left p-0'>
                            <h3 class='beje m-0'>Evento não encontrado</h3>
                        </div>
                        <div class='col-4 text-end p-0'>
                            <a href='?p=eventos' class='btn btn-outline-light btn-sm'>← Voltar aos Eventos</a>
                        </div>
                    </div>
                    <div class='row mt-3'>
                        <div class='col-12'>
                            <div class='alert alert-danger' role='alert'>
                                <h4 class='alert-heading'>Erro!</h4>
                                <p>O evento solicitado não foi encontrado. Verifique se o ID do evento está correto ou se o evento ainda existe.</p>
                            </div>
                        </div>
                    </div>
                </div>
            ";
        }

        extract($evento);
        $nome_evento = $nome;
        // Formatar data
        $data_formatada = date("d/m/Y", strtotime($data));
        $hora_formatada = $hora ? date("H:i", strtotime($hora)) : "N/A";

        // Buscar alunos do evento
        $alunos_result = $this->emotionModel->alunosEvento($id_eventos);
        $alunos_html = "";
        $total_alunos = 0;
        $total_presentes = 0;

        while ($aluno = $alunos_result->fetch_array()) {
            extract($aluno);
            $total_alunos++;

            $nome_aluno = $this->nomeAluno($nome, $alcunha);
            $idade = $this->idade($data_nascimento);
            $foto = $this->fotoAluno($id);

            // Usar dados da estrutura alunos_eventos simplificada
            $presente =
                isset($aluno["id_aluno_evento"]) &&
                $aluno["id_aluno_evento"] > 0;
            $id_presenca = $aluno["id_aluno_evento"] ?? 0;

            if ($presente) {
                $total_presentes++;
            }

            $presente_checked = $presente ? "checked" : "";
            $ausente_checked = !$presente ? "checked" : "";

            $alunos_html .= "
                <tr>
                    <td>{$foto}</td>
                    <td>{$nome_aluno}</td>
                    <td>{$idade} anos</td>
                    <td>
                        <div class='btn-group' role='group'>
                            <input type='radio' class='btn-check presenca-evento' name='presenca-{$id}-{$id_eventos}'
                                   id='presente-{$id}-{$id_eventos}' value='1' {$presente_checked}
                                   data-id-aluno='{$id}' data-id-evento='{$id_eventos}' data-id-presenca='{$id_presenca}' autocomplete='off'>
                            <label class='btn btn-outline-success btn-sm' for='presente-{$id}-{$id_eventos}'>Presente</label>

                            <input type='radio' class='btn-check presenca-evento' name='presenca-{$id}-{$id_eventos}'
                                   id='ausente-{$id}-{$id_eventos}' value='0' {$ausente_checked}
                                   data-id-aluno='{$id}' data-id-evento='{$id_eventos}' data-id-presenca='{$id_presenca}' autocomplete='off'>
                            <label class='btn btn-outline-danger btn-sm' for='ausente-{$id}-{$id_eventos}'>Ausente</label>
                        </div>
                    </td>
                </tr>
            ";
        }

        $total_ausentes = $total_alunos - $total_presentes;
        $percentagem =
            $total_alunos > 0
                ? round(($total_presentes / $total_alunos) * 100)
                : 0;

        $evento_image = $this->fotoEvento($id_eventos);

        return "
            <div class='container'>
                <div class='row bg_azul p-3 align-middle'>
                    <div class='col-8 text-left p-0'>
                        <h3 class='beje m-0'>{$nome_evento}</h3>
                    </div>
                    <div class='col-4 text-end p-0'>
                        <a href='?p=eventos' class='btn btn-outline-light btn-sm'>← Voltar aos Eventos</a>
                    </div>
                </div>

                <div class='row mt-3'>
                    <div class='col-md-4 col-12'>
                        <div class='mb-3'>
                            " .
            ($evento_image
                ? $evento_image
                : "<div class='text-center p-4'><i class='fas fa-image fa-3x text-muted'></i><p class='text-muted mt-2'>Sem imagem</p></div>") .
            "
                        </div>
                    </div>
                    <div class='col-md-8 col-12'>
                        <h5><strong>Categoria:</strong> {$categoria}</h5>
                        <h5><strong>Data:</strong> {$data_formatada}</h5>
                        <h5><strong>Hora:</strong> {$hora_formatada}</h5>
                        <h5><strong>Local:</strong> {$local}</h5>
                        <h5><strong>Localidade:</strong> {$localidade}</h5>
                        " .
            ($observacoes
                ? "<p><strong>Observações:</strong> {$observacoes}</p>"
                : "") .
            "
                        <div class='mt-3 presence-stats'>
                            <p class='fw-bold text-success mb-1' id='presentes-count'>Presentes: {$total_presentes}</p>
                            <p class='fw-bold text-danger mb-1' id='ausentes-count'>Ausentes: {$total_ausentes}</p>
                            <p class='fw-bold mb-1' id='taxa-presenca'>Taxa de Presença: {$percentagem}%</p>
                            <div class='progress'>
                                <div class='progress-bar bg-success' role='progressbar' style='width: {$percentagem}%;'
                                     aria-valuenow='{$percentagem}' aria-valuemin='0' aria-valuemax='100'>{$percentagem}%</div>
                            </div>
                            <div class='mt-3 d-flex gap-2 flex-wrap'>
                                <button type='button' class='btn " .
            ($destaque ? "btn-destaque-on" : "btn-destaque-off") .
            " btn-sm'
                                        id='toggle-destaque-{$id_eventos}'
                                        onclick='toggleDestaque({$id_eventos}, " .
            ($destaque ? "true" : "false") .
            ")'>
                                    <i class='fas fa-star'></i> " .
            ($destaque ? "Em Destaque" : "Sem Destaque") .
            "
                                </button>
                                <a href='?p=evento_professores&id_eventos={$id_eventos}' class='btn btn-primary btn-sm'>
                                    <i class='fas fa-chalkboard-teacher'></i> Gerir Professores
                                </a>
                            </div>
                        </div>

                    </div>
                </div>

                <div class='row mt-4'>
                    <div class='col-12'>
                        <h4>Lista de Alunos</h4>
                        <table class='table table-striped'>
                            <thead>
                                <tr>
                                    <th>Foto</th>
                                    <th>Nome</th>
                                    <th>Idade</th>
                                    <th>Presença</th>
                                </tr>
                            </thead>
                            <tbody>
                                {$alunos_html}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- COMMENTED OUT: Complex upload JavaScript - replaced with simple image display
            <script>
            function triggerImageUpload(eventoId) {
                document.getElementById('evento-image-' + eventoId).click();
            }

            function uploadEventoImage(eventoId, input) {
                if (input.files && input.files[0]) {
                    const formData = new FormData();
                    formData.append('evento_image', input.files[0]);
                    formData.append('id_evento', eventoId);

                    // Show loading
                    const uploadBtn = document.getElementById('upload-btn-' + eventoId);
                    const originalBtnContent = uploadBtn.innerHTML;
                    uploadBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Enviando...';
                    uploadBtn.disabled = true;

                    fetch('upload_evento_image_ultra_simple.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Reload the page to show the new image
                            location.reload();
                        } else {
                            alert('Erro: ' + data.message);
                            uploadBtn.innerHTML = originalBtnContent;
                            uploadBtn.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Erro ao enviar imagem');
                        uploadBtn.innerHTML = originalBtnContent;
                        uploadBtn.disabled = false;
                    });
                }
            }
            </script>
            -->


        ";
    }

    function eventosProfessores($id_eventos)
    {
        // Get event details
        $event_result = $this->emotionModel->evento($id_eventos);
        $event = $event_result->fetch_array();

        if (!$event) {
            return "
                <div class='container'>
                    <div class='row bg_azul p-3 align-middle'>
                        <div class='col-8 text-left p-0'>
                            <h3 class='beje m-0'>Evento não encontrado</h3>
                        </div>
                        <div class='col-4 text-end p-0'>
                            <a href='?p=eventos' class='btn btn-outline-light btn-sm'>← Voltar aos Eventos</a>
                        </div>
                    </div>
                </div>
            ";
        }

        extract($event);
        $nome_evento = $nome;
        $data_formatada = date("d/m/Y", strtotime($data));

        // Get all professors with their event association status
        $professores_result = $this->emotionModel->professoresEvento(
            $id_eventos,
        );
        $professores_html = "";
        $total_professores = 0;
        $total_associados = 0;

        while ($professor = $professores_result->fetch_array()) {
            extract($professor);
            $total_professores++;

            $nome_professor = $this->nomeProfessor($nome, $alcunha);
            $foto = $this->fotoProfessor($id);

            // Check if professor is associated with this event
            $associado =
                isset($professor["id_professor_evento"]) &&
                $professor["id_professor_evento"] > 0;
            $id_associacao = $professor["id_professor_evento"] ?? 0;

            if ($associado) {
                $total_associados++;
            }

            $associado_checked = $associado ? "checked" : "";
            $nao_associado_checked = !$associado ? "checked" : "";

            $professores_html .= "
                <tr>
                    <td>{$foto}</td>
                    <td>{$nome_professor}</td>

                    <td>
                        <div class='btn-group' role='group'>
                            <input type='radio' class='btn-check professor-evento' name='professor-{$id}-{$id_eventos}'
                                   id='presente-{$id}-{$id_eventos}' value='1' {$associado_checked}
                                   data-id-professor='{$id}' data-id-evento='{$id_eventos}' data-id-associacao='{$id_associacao}' autocomplete='off'>
                            <label class='btn btn-outline-success btn-sm' for='presente-{$id}-{$id_eventos}'>Presente</label>

                            <input type='radio' class='btn-check professor-evento' name='professor-{$id}-{$id_eventos}'
                                   id='ausente-{$id}-{$id_eventos}' value='0' {$nao_associado_checked}
                                   data-id-professor='{$id}' data-id-evento='{$id_eventos}' data-id-associacao='{$id_associacao}' autocomplete='off'>
                            <label class='btn btn-outline-danger btn-sm' for='ausente-{$id}-{$id_eventos}'>Ausente</label>
                        </div>
                    </td>
                </tr>
            ";
        }

        $total_nao_associados = $total_professores - $total_associados;

        return "
            <div class='container'>
                <div class='row bg_azul p-3 align-middle'>
                    <div class='col-8 text-left p-0'>
                        <h3 class='beje m-0'>Professores - {$nome_evento}</h3>
                    </div>
                    <div class='col-4 text-end p-0'>
                        <a href='?p=evento&id_eventos={$id_eventos}' class='btn btn-outline-light btn-sm'>← Voltar ao Evento</a>
                    </div>
                </div>

                <div class='row mt-3'>
                    <div class='col-12'>
                        <div class='mb-3'>
                            <p><strong>Evento:</strong> {$nome_evento}</p>
                            <p><strong>Data:</strong> {$data_formatada}</p>
                        </div>

                        <div class='mb-3'>
                            <div class='row'>
                                <div class='col-md-4'>
                                    <div class='card'>
                                        <div class='card-body text-center'>
                                            <h5 class='card-title text-success' id='presentes-count-prof'>{$total_associados}</h5>
                                            <p class='card-text'>Professores Presentes</p>
                                        </div>
                                    </div>
                                </div>
                                <div class='col-md-4'>
                                    <div class='card'>
                                        <div class='card-body text-center'>
                                            <h5 class='card-title text-danger' id='ausentes-count-prof'>{$total_nao_associados}</h5>
                                            <p class='card-text'>Professores Ausentes</p>
                                        </div>
                                    </div>
                                </div>
                                <div class='col-md-4'>
                                    <div class='card'>
                                        <div class='card-body text-center'>
                                            <h5 class='card-title text-info'>{$total_professores}</h5>
                                            <p class='card-text'>Total de Professores</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class='row mt-4'>
                    <div class='col-12'>
                        <h4>Lista de Professores</h4>
                        <table class='table table-striped'>
                            <thead>
                                <tr>
                                    <th>Foto</th>
                                    <th>Nome</th>
                                    <th>Presença</th>
                                </tr>
                            </thead>
                            <tbody>
                                {$professores_html}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        ";
    }

    function toggleEventoDestaque($id_eventos, $destaque)
    {
        // Validate inputs
        $id_eventos = (int) $id_eventos;
        $destaque = (int) $destaque;

        // Start transaction
        $this->emotionModel->mysqli->begin_transaction();

        try {
            // Update destaque status in database
            $query = "UPDATE eventos SET destaque = {$destaque} WHERE id = {$id_eventos} AND ativo = 1";
            $result = $this->emotionModel->mysqli->query($query);

            if (!$result) {
                throw new Exception(
                    "Erro ao atualizar destaque: " .
                        $this->emotionModel->mysqli->error,
                );
            }

            if ($this->emotionModel->mysqli->affected_rows === 0) {
                throw new Exception("Evento não encontrado ou já atualizado");
            }

            // If removing from destaque (destaque = 0), also clear alunos_eventos for this event
            if ($destaque == 0) {
                $clearQuery = "DELETE FROM alunos_eventos WHERE id_eventos = {$id_eventos}";
                $clearResult = $this->emotionModel->mysqli->query($clearQuery);

                if (!$clearResult) {
                    throw new Exception(
                        "Erro ao limpar presenças do evento: " .
                            $this->emotionModel->mysqli->error,
                    );
                }
            }

            // Commit transaction
            $this->emotionModel->mysqli->commit();

            // Get count of cleared records for better feedback
            $clearedCount = 0;
            if ($destaque == 0) {
                $countResult = $this->emotionModel->mysqli->query(
                    "SELECT ROW_COUNT() as cleared",
                );
                if ($countResult) {
                    $countRow = $countResult->fetch_array();
                    $clearedCount = $countRow["cleared"] ?? 0;
                }
            }

            $message = $destaque
                ? "Evento adicionado aos destaques"
                : "Evento removido dos destaques e presenças limpas ($clearedCount registos removidos)";

            return [
                "success" => true,
                "message" => $message,
                "destaque" => $destaque,
            ];
        } catch (Exception $e) {
            // Rollback transaction
            $this->emotionModel->mysqli->rollback();

            return [
                "success" => false,
                "message" => $e->getMessage(),
            ];
        }
    }

    function downloadAllCertificates($id_alunos)
    {
        // Validate input
        $id_alunos = (int) $id_alunos;

        if ($id_alunos <= 0) {
            return [
                "success" => false,
                "message" => "ID do aluno inválido",
            ];
        }

        try {
            // Check if student exists
            $student_result = $this->emotionModel->aluno($id_alunos);
            if (!$student_result || $student_result->num_rows == 0) {
                return [
                    "success" => false,
                    "message" => "Aluno não encontrado",
                ];
            }

            $student_data = $student_result->fetch_array();
            $clean_name = preg_replace(
                "/[^a-zA-Z0-9_\-]/",
                "_",
                $student_data["nome"],
            );

            // Create certificados directory if it doesn't exist
            $certificados_dir = __DIR__ . "/certificados/";
            if (!is_dir($certificados_dir)) {
                mkdir($certificados_dir, 0755, true);
            }

            // Generate current year certificates
            $current_year = date("Y");
            $previous_year = $current_year - 1;
            $ano_letivo = $previous_year . "/" . $current_year;

            $certificate_filename = "certificado_{$clean_name}_{$current_year}.pdf";
            $certificate_path = $certificados_dir . $certificate_filename;

            // Include required files for PDF generation
            require_once __DIR__ . "/fpdf/fpdf.php";

            // Generate the certificate using the same logic as pdf.php
            $this->generateCertificatePDF($id_alunos, $certificate_path);

            // Check if file was created successfully
            if (file_exists($certificate_path)) {
                return [
                    "success" => true,
                    "message" => "Certificado gerado com sucesso: {$certificate_filename}",
                    "download_url" => "certificados/{$certificate_filename}",
                ];
            } else {
                return [
                    "success" => false,
                    "message" => "Erro ao gerar o arquivo do certificado",
                ];
            }
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Erro ao gerar certificados: " . $e->getMessage(),
            ];
        }
    }

    function downloadMultipleCertificates()
    {
        header("Content-Type: application/json");

        try {
            // Get student IDs from POST data
            $student_ids = $_POST["student_ids"] ?? [];

            if (empty($student_ids) || !is_array($student_ids)) {
                echo json_encode([
                    "success" => false,
                    "message" => "Nenhum aluno selecionado",
                ]);
                return;
            }

            $downloads = [];
            $errors = [];

            // Create certificados directory if it doesn't exist
            $certificados_dir = __DIR__ . "/certificados/";
            if (!is_dir($certificados_dir)) {
                mkdir($certificados_dir, 0755, true);
            }

            foreach ($student_ids as $id_alunos) {
                $id_alunos = (int) $id_alunos;

                if ($id_alunos <= 0) {
                    $errors[] = "ID inválido: {$id_alunos}";
                    continue;
                }

                try {
                    // Check if student exists
                    $student_result = $this->emotionModel->aluno($id_alunos);
                    if (!$student_result || $student_result->num_rows == 0) {
                        $errors[] = "Aluno não encontrado: ID {$id_alunos}";
                        continue;
                    }

                    $student_data = $student_result->fetch_array();
                    $clean_name = preg_replace(
                        "/[^a-zA-Z0-9_\-]/",
                        "_",
                        $student_data["nome"],
                    );

                    // Generate current year certificates
                    $current_year = date("Y");
                    $certificate_filename = "certificado_{$clean_name}_{$current_year}.pdf";
                    $certificate_path =
                        $certificados_dir . $certificate_filename;

                    // Generate the certificate
                    $this->generateCertificatePDF(
                        $id_alunos,
                        $certificate_path,
                    );

                    // Check if file was created successfully
                    if (file_exists($certificate_path)) {
                        $downloads[] = [
                            "student_id" => $id_alunos,
                            "student_name" => $student_data["nome"],
                            "filename" => $certificate_filename,
                            "url" => "certificados/" . $certificate_filename,
                        ];
                    } else {
                        $errors[] = "Erro ao gerar certificado para {$student_data["nome"]}";
                    }
                } catch (Exception $e) {
                    $errors[] =
                        "Erro para aluno ID {$id_alunos}: " . $e->getMessage();
                }
            }

            $success_count = count($downloads);
            $error_count = count($errors);

            if ($success_count > 0) {
                $message = "Gerados {$success_count} certificados com sucesso";
                if ($error_count > 0) {
                    $message .= " ({$error_count} erros)";
                }

                echo json_encode([
                    "success" => true,
                    "message" => $message,
                    "downloads" => $downloads,
                    "errors" => $errors,
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Nenhum certificado foi gerado",
                    "errors" => $errors,
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Erro interno: " . $e->getMessage(),
            ]);
        }
    }

    private function generateCertificatePDF($id_alunos, $output_path)
    {
        // This function uses the same logic as pdf.php but saves to file instead of outputting
        // Include the certificate generation logic here
        // For now, we'll use a simple approach - call the pdf.php script internally

        $pdf_url =
            "http://" .
            $_SERVER["HTTP_HOST"] .
            dirname($_SERVER["PHP_SELF"]) .
            "/pdf.php?id_alunos={$id_alunos}&generate=1";

        // Use cURL or file_get_contents to get the PDF content
        $pdf_content = file_get_contents($pdf_url);

        if ($pdf_content !== false) {
            file_put_contents($output_path, $pdf_content);
        } else {
            throw new Exception("Erro ao gerar conteúdo do PDF");
        }
    }

    function exportStudentsList()
    {
        try {
            // Get data from POST (now JSON-encoded)
            $headers = isset($_POST["headers"])
                ? json_decode($_POST["headers"], true)
                : [];
            $students_data = isset($_POST["students_data"])
                ? json_decode($_POST["students_data"], true)
                : [];

            if (empty($students_data)) {
                header("Content-Type: application/json");
                echo json_encode([
                    "success" => false,
                    "message" => "Nenhum dado de aluno encontrado",
                ]);
                return;
            }

            // Generate filename with timestamp
            $timestamp = date("Y-m-d_H-i-s");
            $filename = "lista_alunos_{$timestamp}.csv";

            // Set headers for direct download
            header("Content-Type: text/csv; charset=utf-8");
            header("Content-Disposition: attachment; filename=\"{$filename}\"");
            header("Cache-Control: no-cache, must-revalidate");
            header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

            // Output UTF-8 BOM for proper Excel encoding
            echo "\xEF\xBB\xBF";

            // Output headers
            if (!empty($headers)) {
                echo implode(";", $headers) . "\r\n";
            }

            // Output student data
            foreach ($students_data as $row) {
                // Clean and escape data for CSV
                $clean_row = array_map(function ($cell) {
                    // Remove any HTML tags and clean the data
                    $cell = strip_tags($cell);
                    $cell = str_replace('"', '""', $cell); // Escape quotes
                    return '"' . $cell . '"'; // Wrap in quotes
                }, $row);
                echo implode(";", $clean_row) . "\r\n";
            }

            exit(); // Stop execution after sending file
        } catch (Exception $e) {
            header("Content-Type: application/json");
            echo json_encode([
                "success" => false,
                "message" => "Erro interno: " . $e->getMessage(),
            ]);
        }
    }
}

#AJAX
if (isset($_POST["function"])) {
    require_once "model.php";
    $emotionModel = new EmotionModel();
    $emotionController = new EmotionController();

    switch ($_POST["function"]) {
        case "aula":
            if ($_POST["action"] == "insert") {
                $emotionModel->createAlunoAula(
                    $_POST["id_alunos"],
                    $_POST["id_aulas"],
                    $_POST["dia"],
                );
            } else {
                $emotionModel->removeAlunoAula(
                    $_POST["id_alunos"],
                    $_POST["id_aulas"],
                    $_POST["dia"],
                );
            }

            $mensalidade = $emotionController->mensalidade($_POST["id_alunos"]);
            echo $mensalidade;
            break;

        case "turma":
            echo $emotionController->turma(
                $_POST["id_aulas"],
                $_POST["dia"],
                $_POST["data"],
            );
            break;

        case "presencas":
            if ($_POST["action"] == "insert") {
                $horas = date("H:i:s");
                $emotionModel->createPresenca(
                    $_POST["id_alunos"],
                    $_POST["id_aulas"],
                    $_POST["dia"],
                    1,
                    "{$_POST["data"]} {$horas}",
                );
            } else {
                $emotionModel->removePresenca(
                    $_POST["id_alunos"],
                    $_POST["id_aulas"],
                    $_POST["dia"],
                    "{$_POST["data"]}",
                );
            }
            break;

        case "dataAtual":
            echo date("Y-m-d");
            break;

        case "presencasObservacoes":
            $emotionModel->presencasObservacoes(
                $_POST["id_presencas"],
                "{$_POST["observacoes"]}",
            );
            break;

        case "presencasPresente":
            // Clean any previous output
            if (ob_get_level()) {
                ob_clean();
            }

            // Validate required parameters
            if (!isset($_POST["id_presencas"]) || !isset($_POST["presente"])) {
                header("Content-Type: application/json");
                echo json_encode([
                    "success" => false,
                    "error" => "Missing required parameters",
                ]);
                exit();
            }

            // Update presence first
            $result = $emotionModel->presencasPresente(
                $_POST["id_presencas"],
                $_POST["presente"],
            );

            if (!$result) {
                header("Content-Type: application/json");
                echo json_encode([
                    "success" => false,
                    "error" => "Failed to update presence - ID may not exist",
                ]);
                exit();
            }

            // Return updated statistics if requested
            if (
                isset($_POST["return_stats"]) &&
                $_POST["return_stats"] == "true"
            ) {
                $id_aulas = $_POST["id_aulas"] ?? null;
                $data = $_POST["data"] ?? null;

                if (!$id_aulas || !$data) {
                    header("Content-Type: application/json");
                    echo json_encode([
                        "success" => false,
                        "error" => "Missing aula or date for stats",
                    ]);
                    exit();
                }

                // Get updated statistics - use $emotionController instead of $this
                $result_aula = $emotionController->aulasProfessores(
                    $id_aulas,
                    "",
                    "",
                    $data,
                    $data,
                );

                if ($result_aula && $result_aula->num_rows > 0) {
                    $row_aula = $result_aula->fetch_array();

                    $assiduidade = $emotionController->assiduidade(
                        $row_aula["presentes"],
                        $row_aula["ausentes"],
                        "",
                    );
                    $total_presencas =
                        $assiduidade["presentes"] + $assiduidade["ausentes"];

                    $stats = [
                        "success" => true,
                        "presentes" => (int) $row_aula["presentes"],
                        "ausentes" => (int) $row_aula["ausentes"],
                        "total" => $total_presencas,
                        "assiduidade_percentage" => $assiduidade[0],
                        "assiduidade_bar" => $assiduidade[1],
                    ];
                } else {
                    // Fallback: calculate stats directly from presencas table
                    $direct_stats_query =
                        "
                        SELECT
                            SUM(CASE WHEN presente = 1 THEN 1 ELSE 0 END) as presentes,
                            SUM(CASE WHEN presente = 0 THEN 1 ELSE 0 END) as ausentes,
                            COUNT(*) as total
                        FROM presencas
                        WHERE id_aulas = " .
                        (int) $id_aulas .
                        "
                        AND DATE_FORMAT(data, '%Y-%m-%d') = '" .
                        $emotionModel->mysqli->real_escape_string($data) .
                        "'
                    ";

                    $direct_result = $emotionModel->mysqli->query(
                        $direct_stats_query,
                    );

                    if ($direct_result && $direct_result->num_rows > 0) {
                        $direct_stats = $direct_result->fetch_assoc();

                        if ($direct_stats["total"] > 0) {
                            $assiduidade = $emotionController->assiduidade(
                                $direct_stats["presentes"],
                                $direct_stats["ausentes"],
                                "",
                            );

                            $stats = [
                                "success" => true,
                                "presentes" => (int) $direct_stats["presentes"],
                                "ausentes" => (int) $direct_stats["ausentes"],
                                "total" => (int) $direct_stats["total"],
                                "assiduidade_percentage" => $assiduidade[0],
                                "assiduidade_bar" => $assiduidade[1],
                            ];
                        } else {
                            // No presences found for this date
                            $stats = [
                                "success" => true,
                                "presentes" => 0,
                                "ausentes" => 0,
                                "total" => 0,
                                "assiduidade_percentage" => 0,
                                "assiduidade_bar" =>
                                    '<div class="progress"><div class="progress-bar bg-danger" style="width: 0%;">0%</div></div>',
                            ];
                        }
                    } else {
                        // Query failed, return zeros
                        $stats = [
                            "success" => true,
                            "presentes" => 0,
                            "ausentes" => 0,
                            "total" => 0,
                            "assiduidade_percentage" => 0,
                            "assiduidade_bar" =>
                                '<div class="progress"><div class="progress-bar bg-danger" style="width: 0%;">0%</div></div>',
                        ];
                    }
                }

                // Ensure clean JSON output
                header("Content-Type: application/json");
                echo json_encode($stats);
                exit();
            } else {
                // Simple response for non-stats requests
                header("Content-Type: application/json");
                echo json_encode(["success" => true]);
                exit();
            }
            break;

        case "apagaPresenca":
            $emotionModel->apagaPresenca($_POST["id_presencas"]);
            break;

        case "getAulaStats":
            $id_aulas = $_POST["id_aulas"];
            $data = $_POST["data"];

            // Get updated statistics - fix: pass 0 as first parameter (id) to get all aulas for the date
            $result_aula = $emotionController->aulasProfessores(
                0,
                "",
                "",
                $data,
                $data,
            );

            // Find the specific aula in the results
            $row_aula = null;
            while ($row = $result_aula->fetch_array()) {
                if ($row["id_aulas"] == $id_aulas) {
                    $row_aula = $row;
                    break;
                }
            }

            if ($row_aula) {
                $assiduidade = $emotionController->assiduidade(
                    $row_aula["presentes"],
                    $row_aula["ausentes"],
                    "",
                );
                $total_presencas =
                    $row_aula["presentes"] + $row_aula["ausentes"];

                $stats = [
                    "presentes" => (int) $row_aula["presentes"],
                    "ausentes" => (int) $row_aula["ausentes"],
                    "total" => (int) $total_presencas,
                    "assiduidade_percentage" => (int) $assiduidade[0],
                    "assiduidade_bar" => $assiduidade[1],
                ];
            } else {
                // If no data found, return zeros
                $stats = [
                    "presentes" => 0,
                    "ausentes" => 0,
                    "total" => 0,
                    "assiduidade_percentage" => 0,
                    "assiduidade_bar" =>
                        '<div class="progress"><div class="progress-bar bg-secondary" role="progressbar" style="width: 0%;">0%</div></div>',
                ];
            }

            echo json_encode($stats);
            break;

        case "fotoAluno":
            try {
                $id = $_POST["id"] ?? 0;
                if ($id > 0) {
                    $foto = $emotionController->fotoAluno($id);
                    echo $foto ? $foto : "";
                } else {
                    echo "";
                }
            } catch (Exception $e) {
                echo "";
            }
            break;

        case "presencaEvento":
            header("Content-Type: application/json");

            // Debug logging
            error_log("=== PRESENCA EVENTO DEBUG ===");
            error_log("POST data: " . print_r($_POST, true));
            error_log("Timestamp: " . date("Y-m-d H:i:s"));

            try {
                $id_aluno = $_POST["id_aluno"];
                $id_evento = $_POST["id_evento"];
                $id_presenca = $_POST["id_presenca"];
                $presente = intval($_POST["presente"]);
                $action = $_POST["action"];

                error_log(
                    "Parsed values - id_aluno: $id_aluno, id_evento: $id_evento, id_presenca: $id_presenca, presente: $presente, action: $action",
                );

                if ($presente == 1) {
                    error_log("Marking as present");
                    // Marcar como presente - sempre usar createPresencaEvento
                    $success = $emotionModel->createPresencaEvento(
                        $id_aluno,
                        $id_evento,
                        $presente,
                    );

                    error_log(
                        "createPresencaEvento result: " .
                            ($success ? "true" : "false"),
                    );

                    if ($success) {
                        // Buscar o ID da presença criada/atualizada
                        $result = $emotionModel->presencaEventoAluno(
                            $id_evento,
                            $id_aluno,
                        );
                        if ($result && $result->num_rows > 0) {
                            $row = $result->fetch_array();
                            error_log(
                                "Found presence record with ID: " . $row["id"],
                            );
                            echo json_encode([
                                "success" => true,
                                "id_presenca" => $row["id"],
                            ]);
                        } else {
                            error_log(
                                "Could not find presence record after creation",
                            );
                            echo json_encode([
                                "success" => false,
                                "message" => "Erro ao obter ID da presença",
                            ]);
                        }
                    } else {
                        error_log("Failed to create presence record");
                        echo json_encode([
                            "success" => false,
                            "message" => "Erro ao marcar como presente",
                        ]);
                    }
                } else {
                    error_log("Marking as absent");
                    // Marcar como ausente - remover o registro
                    $success = $emotionModel->removePresencaEvento(
                        $id_aluno,
                        $id_evento,
                    );
                    error_log(
                        "removePresencaEvento result: " .
                            ($success ? "true" : "false"),
                    );
                    echo json_encode([
                        "success" => $success,
                        "id_presenca" => 0,
                        "message" => $success
                            ? "Marcado como ausente"
                            : "Erro ao marcar como ausente",
                    ]);
                }
            } catch (Exception $e) {
                error_log("Exception in presencaEvento: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                echo json_encode([
                    "success" => false,
                    "message" => $e->getMessage(),
                ]);
            }
            break;

        case "professorEvento":
            header("Content-Type: application/json");
            try {
                $id_professor = $_POST["id_professor"];
                $id_evento = $_POST["id_evento"];
                $id_associacao = $_POST["id_associacao"];
                $presente = intval($_POST["presente"]);
                $action = $_POST["action"];

                if ($presente == 1) {
                    // Marcar professor como presente
                    $success = $emotionModel->createProfessorEvento(
                        $id_professor,
                        $id_evento,
                    );

                    if ($success) {
                        // Buscar o ID da associação criada
                        $result = $emotionModel->mysqli->query("
                            SELECT id FROM professores_eventos
                            WHERE id_professores = '{$id_professor}' AND id_eventos = '{$id_evento}'
                        ");
                        if ($result && $result->num_rows > 0) {
                            $row = $result->fetch_array();
                            echo json_encode([
                                "success" => true,
                                "id_associacao" => $row["id"],
                            ]);
                        } else {
                            echo json_encode([
                                "success" => false,
                                "message" => "Erro ao obter ID da associação",
                            ]);
                        }
                    } else {
                        echo json_encode([
                            "success" => false,
                            "message" => "Erro ao associar professor",
                        ]);
                    }
                } else {
                    // Marcar professor como ausente
                    $success = $emotionModel->removeProfessorEvento(
                        $id_professor,
                        $id_evento,
                    );
                    echo json_encode([
                        "success" => $success,
                        "id_associacao" => 0,
                        "message" => $success
                            ? "Professor marcado como ausente"
                            : "Erro ao marcar como ausente",
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    "success" => false,
                    "message" => $e->getMessage(),
                ]);
            }
            break;

        case "toggleEventoDestaque":
            header("Content-Type: application/json");
            try {
                $id_eventos = $_POST["id_eventos"] ?? 0;
                $destaque = $_POST["destaque"] ?? 0;

                $result = $emotionController->toggleEventoDestaque(
                    $id_eventos,
                    $destaque,
                );
                echo json_encode($result);
            } catch (Exception $e) {
                echo json_encode([
                    "success" => false,
                    "message" => $e->getMessage(),
                ]);
            }
            break;

        case "downloadMultipleCertificates":
            $emotionController->downloadMultipleCertificates();
            break;

        case "exportStudentsList":
            $emotionController->exportStudentsList();
            break;
    }
}
#/AJAX
?>
