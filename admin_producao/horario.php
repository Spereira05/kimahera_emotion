<?php

// Enhanced Horário page with improved autocomplete functionality

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

    // Get student data for autocomplete
    $students_result = $emotionModel->alunos("`alunos`.`nome` ASC", "");
    $students_data = [];
    $selected_student_info = null;

    while ($row = $students_result->fetch_array()) {
        $idade = $emotionController->idade($row["data_nascimento"]);
        $student = [
            "id" => $row["id"],
            "nome" => $row["nome"],
            "alcunha" => $row["alcunha"] ?? "",
            "idade" => $idade,
            "foto" => $emotionController->fotoAluno($row["id"]),
            "display" =>
                trim($row["nome"] . " " . ($row["alcunha"] ?? "")) .
                " ({$idade} anos)",
        ];
        $students_data[] = $student;

        // If this is the selected student
        if (isset($_GET["id_alunos"]) && $_GET["id_alunos"] == $row["id"]) {
            $selected_student_info = $student;
        }
    }

    $students_json = json_encode($students_data);
    $selected_id = $_GET["id_alunos"] ?? "";
    $selected_display = $selected_student_info
        ? $selected_student_info["display"]
        : "";

    echo "
    <div class='container-fluid'>
        <div class='row'>
            <div class='col-12'>
                <div class='card shadow-sm'>
                    <div class='card-header bg_azul text-white'>
                        <h4 class='mb-0 beje'>
                            <i class='fas fa-calendar-alt me-2'></i>
                            Horário dos Alunos
                        </h4>
                    </div>
                    <div class='card-body'>

                        <!-- Enhanced Student Search Section -->
                        <div class='row mb-4'>
                            <div class='col-lg-8'>
                                <label for='student-search-enhanced' class='form-label fw-bold'>
                                    <i class='fas fa-search me-1'></i>
                                    Pesquisar Aluno:
                                </label>
                                <div class='position-relative'>
                                    <input
                                        type='text'
                                        id='student-search-enhanced'
                                        class='form-control form-control-lg shadow-sm'
                                        placeholder='Digite o nome do aluno, alcunha ou ID...'
                                        value='{$selected_display}'
                                        autocomplete='off'
                                        style='padding-right: 50px;'
                                    >
                                    <input type='hidden' id='selected-student-id' value='{$selected_id}'>

                                    <!-- Search Icon -->
                                    <span class='position-absolute top-50 end-0 translate-middle-y me-3 text-muted'>
                                        <i class='fas fa-search'></i>
                                    </span>

                                    <!-- Clear Button -->
                                    <button type='button' id='clear-search-btn' class='btn btn-outline-secondary btn-sm position-absolute top-50 end-0 translate-middle-y me-5 d-none' style='z-index: 10;'>
                                        <i class='fas fa-times'></i>
                                    </button>

                                    <!-- Loading Spinner -->
                                    <div id='search-loading' class='position-absolute top-50 end-0 translate-middle-y me-3 d-none'>
                                        <div class='spinner-border spinner-border-sm' style='color: #a92b4d;' role='status'>
                                            <span class='visually-hidden'>Carregando...</span>
                                        </div>
                                    </div>

                                    <!-- Autocomplete Results -->
                                    <div id='autocomplete-dropdown' class='autocomplete-dropdown position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-lg d-none' style='z-index: 1000; max-height: 400px; overflow-y: auto;'>
                                        <!-- Results populated by JavaScript -->
                                    </div>
                                </div>


                            </div>

                            <div class='col-lg-4'>
                                <!-- Selected Student Info -->
                                <div id='selected-student-display' class='mt-4 mt-lg-0'>
                                    " .
        ($selected_student_info
            ? "
                                    <div class='card bg-light'>
                                        <div class='card-body text-center'>
                                            {$selected_student_info["foto"]}
                                            <h6 class='card-title mt-2 mb-1'>{$selected_student_info["nome"]}</h6>
                                            <small class='text-muted'>{$selected_student_info["idade"]} anos • ID: {$selected_student_info["id"]}</small>
                                        </div>
                                    </div>
                                    "
            : "
                                    <div class='text-center text-muted p-4'>
                                        <i class='fas fa-user-circle fa-3x mb-2'></i>
                                        <p class='mb-0'>Nenhum aluno selecionado</p>
                                        <small>Use a pesquisa para selecionar um aluno</small>
                                    </div>
                                    ") .
        "
                                </div>
                            </div>
                        </div>

                        <!-- Horário Content -->
                        <div id='horario-content'>
                            " .
        ($selected_id
            ? $emotionController->horario()
            : "
                            <div class='text-center text-muted p-5'>
                                <i class='fas fa-calendar-times fa-4x mb-3'></i>
                                <h5>Selecione um aluno para ver o horário</h5>
                                <p>Use a barra de pesquisa acima para encontrar e selecionar um aluno.</p>
                            </div>
                            ") .
        "
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    ";
    ?>

<script>
$(document).ready(function() {
    const students = <?php echo $students_json; ?>;
    const searchInput = $('#student-search-enhanced');
    const dropdown = $('#autocomplete-dropdown');
    const clearBtn = $('#clear-search-btn');
    const loadingSpinner = $('#search-loading');
    const selectedDisplay = $('#selected-student-display');
    const horarioContent = $('#horario-content');

    let selectedIndex = -1;
    let searchTimeout;

    // Show/hide clear button
    function toggleClearButton() {
        if (searchInput.val().length > 0) {
            clearBtn.removeClass('d-none');
        } else {
            clearBtn.addClass('d-none');
        }
    }

    // Filter students based on query
    function filterStudents(query) {
        query = query.toLowerCase();
        return students.filter(student =>
            student.nome.toLowerCase().includes(query) ||
            (student.alcunha && student.alcunha.toLowerCase().includes(query)) ||
            student.id.toString().includes(query)
        ).sort((a, b) => {
            // Prioritize exact matches at the beginning
            const aName = a.nome.toLowerCase();
            const bName = b.nome.toLowerCase();

            if (aName.startsWith(query) && !bName.startsWith(query)) return -1;
            if (!aName.startsWith(query) && bName.startsWith(query)) return 1;

            return aName.localeCompare(bName);
        });
    }

    // Render autocomplete results
    function renderResults(filteredStudents) {
        if (filteredStudents.length === 0) {
            dropdown.html(`
                <div class='no-results'>
                    <i class='fas fa-user-slash fa-2x mb-2' style='color: #a92b4d;'></i>
                    <p class='mb-0'>Nenhum aluno encontrado</p>
                    <small>Tente pesquisar com termos diferentes</small>
                </div>
            `);
            return;
        }

        let html = '';
        filteredStudents.forEach((student, index) => {
            html += `
                <div class='autocomplete-item' data-student-id='${student.id}' data-index='${index}'>
                    <div class='student-info'>
                        <div class='student-name'>${student.nome}${student.alcunha ? ' (' + student.alcunha + ')' : ''}</div>
                        <div class='student-details'>${student.idade} anos • ID: ${student.id}</div>
                    </div>
                </div>
            `;
        });
        dropdown.html(html);
    }

    // Update selection highlight
    function updateSelection() {
        const items = dropdown.find('.autocomplete-item');
        items.removeClass('selected');

        if (selectedIndex >= 0 && selectedIndex < items.length) {
            items.eq(selectedIndex).addClass('selected');
        }
    }

    // Select a student
    function selectStudent(studentId) {
        const student = students.find(s => s.id == studentId);
        if (!student) return;

        // Update input and hidden field
        searchInput.val(student.display);
        $('#selected-student-id').val(studentId);

        // Hide dropdown
        dropdown.addClass('d-none');
        selectedIndex = -1;

        // Update selected student display
        const photoHtml = student.foto || '<img src="img/user-default.png" class="foto" alt="Sem foto">';
        selectedDisplay.html(`
            <div class='card bg-light'>
                <div class='card-body text-center'>
                    ${photoHtml}
                    <h6 class='card-title mt-2 mb-1'>${student.nome}</h6>
                    <small class='text-muted'>${student.idade} anos • ID: ${student.id}</small>
                </div>
            </div>
        `);

        // Load horario content
        loadingSpinner.removeClass('d-none');
        $.get(window.location.pathname, { p: 'horario', id_alunos: studentId })
            .done(function(data) {
                // Redirect to update URL and reload page
                window.location.href = '?p=horario&id_alunos=' + studentId;
            })
            .fail(function() {
                alert('Erro ao carregar horário do aluno');
            })
            .always(function() {
                loadingSpinner.addClass('d-none');
            });
    }

    // Search input handler
    searchInput.on('input', function() {
        const query = $(this).val();
        toggleClearButton();

        // Clear previous timeout
        clearTimeout(searchTimeout);

        if (query.length < 2) {
            dropdown.addClass('d-none');
            selectedIndex = -1;
            return;
        }

        // Debounce search
        searchTimeout = setTimeout(() => {
            const filteredStudents = filterStudents(query);
            renderResults(filteredStudents);
            dropdown.removeClass('d-none');
            selectedIndex = -1;
        }, 150);
    });

    // Keyboard navigation
    searchInput.on('keydown', function(e) {
        const items = dropdown.find('.autocomplete-item');

        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                updateSelection();
                break;

            case 'ArrowUp':
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                updateSelection();
                break;

            case 'Enter':
                e.preventDefault();
                if (selectedIndex >= 0 && items.length > 0) {
                    const studentId = items.eq(selectedIndex).data('student-id');
                    selectStudent(studentId);
                }
                break;

            case 'Escape':
                dropdown.addClass('d-none');
                selectedIndex = -1;
                break;
        }
    });

    // Click selection
    $(document).on('click', '.autocomplete-item', function() {
        const studentId = $(this).data('student-id');
        selectStudent(studentId);
    });

    // Clear button
    clearBtn.on('click', function() {
        searchInput.val('');
        $('#selected-student-id').val('');
        dropdown.addClass('d-none');
        toggleClearButton();

        // Reset display
        selectedDisplay.html(`
            <div class='text-center text-muted p-4'>
                <i class='fas fa-user-circle fa-3x mb-2'></i>
                <p class='mb-0'>Nenhum aluno selecionado</p>
                <small>Use a pesquisa para selecionar um aluno</small>
            </div>
        `);

        searchInput.focus();
    });

    // Hide dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#student-search-enhanced, #autocomplete-dropdown').length) {
            dropdown.addClass('d-none');
            selectedIndex = -1;
        }
    });

    // Focus on search input on page load
    searchInput.focus();

    // Initialize clear button state
    toggleClearButton();
});
</script>

<?php
} catch (Exception $e) {
    echo "
        <div class='alert alert-danger' role='alert'>
            <h4 class='alert-heading'>Erro</h4>
            <p>Ocorreu um erro ao carregar a página: " .
        htmlspecialchars($e->getMessage()) .
        "</p>
        </div>
    ";
}

?>
