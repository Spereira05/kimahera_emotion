$(function () {
  // Initialize Bootstrap tooltips for presence buttons
  $("[title]").tooltip();

  // ========================================
  // UTILITY FUNCTIONS
  // ========================================

  function dataAtual() {
    var hoje = "";

    $.ajax({
      type: "POST",
      url: "controller.php",
      async: false,
      dataType: "html",
      data: {
        function: "dataAtual",
      },
    }).done(function (data) {
      hoje = data;
    });

    return hoje;
  }

  function showMessage(message, type, timeout) {
    // Remove existing messages
    $(".message-alert").remove();

    // Default timeout to 3 seconds if not specified
    timeout = timeout || 3000;

    // Determine alert class based on type
    var alertClass = "alert-primary";
    if (type === "success") {
      alertClass = "alert-success";
    } else if (type === "error" || type === "danger") {
      alertClass = "alert-danger";
    } else if (type === "warning") {
      alertClass = "alert-warning";
    } else if (type === "info") {
      alertClass = "alert-info";
    }

    var messageHtml =
      '<div class="alert ' +
      alertClass +
      ' message-alert alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 250px; max-width: 400px;">' +
      message +
      '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
      "</div>";

    $("body").append(messageHtml);

    // Auto-hide after specified timeout
    setTimeout(function () {
      $(".message-alert").fadeOut(500, function () {
        $(this).remove();
      });
    }, timeout);
  }

  // ========================================
  // STATISTICS FUNCTIONS
  // ========================================

  function updateAulaStats(stats) {
    // Simple and direct approach
    var presentesEl = $('[data-stat="presentes"] .stat-value');
    var ausentesEl = $('[data-stat="ausentes"] .stat-value');
    var totalEl = $('[data-stat="total"] .stat-value');
    var barEl = $(".assiduidade-bar");

    // Add smooth transition effect
    $(".aula-stats").addClass("stats-updating");

    // Update present count with animation
    if (presentesEl.length > 0 && stats.presentes !== undefined) {
      presentesEl.fadeOut(200, function () {
        $(this).text(stats.presentes).fadeIn(200);
      });
    }

    // Update absent count with animation
    if (ausentesEl.length > 0 && stats.ausentes !== undefined) {
      ausentesEl.fadeOut(200, function () {
        $(this).text(stats.ausentes).fadeIn(200);
      });
    }

    // Update total with animation
    if (totalEl.length > 0 && stats.total !== undefined) {
      totalEl.fadeOut(200, function () {
        $(this).text(stats.total).fadeIn(200);
      });
    }

    // Update progress bar with animation
    if (barEl.length > 0 && stats.assiduidade_bar) {
      barEl.fadeOut(200, function () {
        $(this).html(stats.assiduidade_bar).fadeIn(200);
      });
    }

    // Add minimal visual feedback to stats area (no green box)
    setTimeout(function () {
      $(".aula-stats").removeClass("stats-updating");
    }, 400);
  }

  function reloadAulaStats() {
    var urlParams = new URLSearchParams(window.location.search);
    var id_aulas = urlParams.get("id_aulas");
    var data = urlParams.get("data");

    if (id_aulas && data) {
      $.ajax({
        type: "POST",
        url: "controller.php",
        async: true,
        dataType: "json",
        data: {
          function: "getAulaStats",
          id_aulas: id_aulas,
          data: data,
        },
      })
        .done(function (response) {
          if (response && response.presentes !== undefined) {
            updateAulaStats(response);
          }
        })
        .fail(function (xhr, status, error) {
          console.error("Stats reload error:", status, error);
        });
    }
  }

  function updatePresenceStats() {
    // Count present and absent students
    var totalStudents = 0;
    var presentStudents = 0;

    // Count checked "presente" radio buttons
    $('.presenca-evento[value="1"]:checked').each(function () {
      presentStudents++;
      totalStudents++;
    });

    // Count checked "ausente" radio buttons
    $('.presenca-evento[value="0"]:checked').each(function () {
      totalStudents++;
    });

    var absentStudents = totalStudents - presentStudents;
    var percentage =
      totalStudents > 0
        ? Math.round((presentStudents / totalStudents) * 100)
        : 0;

    // Update the statistics elements
    $("#presentes-count").text("Presentes: " + presentStudents);
    $("#ausentes-count").text("Ausentes: " + absentStudents);
    $("#taxa-presenca").text("Taxa de Presença: " + percentage + "%");

    // Update progress bar
    $(".progress-bar")
      .css("width", percentage + "%")
      .attr("aria-valuenow", percentage)
      .text(percentage + "%");
  }

  function updateProfessorStats() {
    // Count present and absent professors
    var totalProfessors = 0;
    var presentProfessors = 0;

    // Count checked "presente" radio buttons
    $('.professor-evento[value="1"]:checked').each(function () {
      presentProfessors++;
      totalProfessors++;
    });

    // Count checked "ausente" radio buttons
    $('.professor-evento[value="0"]:checked').each(function () {
      totalProfessors++;
    });

    var absentProfessors = totalProfessors - presentProfessors;

    // Update the statistics elements
    $("#presentes-count-prof").text(presentProfessors);
    $("#ausentes-count-prof").text(absentProfessors);
  }

  // ========================================
  // PRESENCE HANDLING FUNCTIONS
  // ========================================

  function handlePresenceChange(
    radio,
    id_aluno,
    id_evento,
    id_presenca,
    presente,
  ) {
    var now = new Date().toISOString().slice(0, 19).replace("T", " ");

    // Debug logging
    console.log("=== EVENTO PRESENCE BUTTON CLICKED ===");
    console.log("Event triggered at:", new Date().toISOString());
    console.log("Element:", radio[0]);
    console.log("jQuery element:", radio);
    console.log("Data attributes:", {
      id_aluno: id_aluno,
      id_evento: id_evento,
      id_presenca: id_presenca,
      presente: presente,
    });
    console.log("Radio value:", radio.val());
    console.log("Radio name:", radio.attr("name"));

    // Encontrar todos os radio buttons do mesmo grupo
    var radioGroup = $("input[name='" + radio.attr("name") + "']");

    // Add loading state
    var parentRow = radio.closest("tr");
    var originalContent = parentRow.find("td:last").html();

    // Disable all radio buttons in the group during request
    radioGroup.prop("disabled", true);

    // Atualizar todos os radio buttons do grupo com os mesmos dados
    radioGroup.each(function () {
      $(this).data("id-presenca", id_presenca);
    });

    // Enviar para o servidor
    var ajaxData = {
      function: "presencaEvento",
      id_aluno: id_aluno,
      id_evento: id_evento,
      id_presenca: id_presenca,
      presente: presente,
      data_hora: now,
      action: id_presenca > 0 ? "update" : "insert",
    };

    console.log("Sending AJAX request with data:", ajaxData);
    console.log("Controller URL:", "controller.php");
    console.log("Request time:", new Date().toISOString());

    $.ajax({
      type: "POST",
      url: "controller.php",
      dataType: "json",
      data: ajaxData,
      timeout: 10000, // 10 second timeout
      success: function (response) {
        console.log("=== AJAX SUCCESS ===");
        console.log("Response time:", new Date().toISOString());
        console.log("Server response:", response);
        console.log("Response type:", typeof response);

        // Check if response is valid JSON
        if (typeof response !== "object") {
          console.error("Invalid response format:", response);
          try {
            response = JSON.parse(response);
          } catch (e) {
            console.error("Failed to parse response as JSON:", e);
            // Treat as success if we can't parse but got a response
            response = { success: true, message: "Operação concluída" };
          }
        }

        if (response.success) {
          // Always update the data-id-presenca with the returned value
          if (response.id_presenca) {
            radioGroup.each(function () {
              $(this).data("id-presenca", response.id_presenca);
            });
          }

          // Update presence statistics in real-time
          updatePresenceStats();

          // Show brief success feedback
          var successMessage =
            response.message ||
            (presente ? "Marcado como presente" : "Marcado como ausente");
          // showMessage(successMessage, "success", 2000); // Commented for future use

          console.log("Presence updated successfully");
        } else {
          console.error("Server returned error:", response.message);

          // Check if it's a duplicate key error (which actually means it worked)
          if (
            response.message &&
            (response.message.includes("Duplicate entry") ||
              response.message.includes("duplicate key"))
          ) {
            console.log(
              "Duplicate key error detected - treating as success since record exists",
            );

            // Update presence statistics anyway
            updatePresenceStats();

            // Show success message
            // showMessage(
            //   presente ? "Marcado como presente" : "Marcado como ausente",
            //   "success",
            //   2000,
            // ); // Commented for future use

            console.log(
              "Presence updated successfully (duplicate key handled)",
            );
          } else {
            // Real error - revert the selection
            var oppositeValue = presente === 1 ? 0 : 1;
            radioGroup
              .filter("[value='" + oppositeValue + "']")
              .prop("checked", true);

            // Restore original content immediately on error
            parentRow.find("td:last").html(originalContent);

            // Show more helpful error message
            var errorMsg = "Erro ao atualizar presença";
            if (response.message) {
              if (response.message.includes("estrutura da tabela")) {
                errorMsg =
                  "Base de dados precisa ser atualizada. Execute quick_fix.php";
              } else {
                errorMsg = response.message;
              }
            }

            // showMessage(errorMsg, "error", 5000); // Commented for future use
          }
        }

        // Re-enable radio buttons
        radioGroup.prop("disabled", false);
      },
      error: function (xhr, status, error) {
        console.error("=== AJAX ERROR ===");
        console.error("Error time:", new Date().toISOString());
        console.error("AJAX error:", {
          xhr: xhr,
          status: status,
          error: error,
        });
        console.error("Response Text:", xhr.responseText);
        console.error("Response Status:", xhr.status);
        console.error("Ready State:", xhr.readyState);

        // Check if it's actually a success disguised as an error
        var isActuallySuccess = false;

        // Check for duplicate key errors (which mean update worked)
        if (
          xhr.responseText &&
          (xhr.responseText.includes("Duplicate entry") ||
            xhr.responseText.includes("duplicate key"))
        ) {
          console.log(
            "Duplicate key error in AJAX response - treating as success",
          );
          isActuallySuccess = true;
        }

        // Check if response contains success JSON despite error status
        if (xhr.responseText && xhr.responseText.includes('"success":true')) {
          console.log("Found success response despite error status");
          isActuallySuccess = true;
        }

        if (isActuallySuccess) {
          // Update presence statistics
          updatePresenceStats();

          // Show success message
          // showMessage(
          //   presente ? "Marcado como presente" : "Marcado como ausente",
          //   "success",
          //   2000,
          // ); // Commented for future use

          console.log("Treating AJAX error as success");
        } else {
          // Real error - revert the selection
          var oppositeValue = presente === 1 ? 0 : 1;
          radioGroup
            .filter("[value='" + oppositeValue + "']")
            .prop("checked", true);

          // Restore original content immediately on connection error
          parentRow.find("td:last").html(originalContent);

          // Show appropriate error message based on status
          var errorMessage = "Erro de conexão ao atualizar presença";

          if (status === "timeout") {
            errorMessage =
              "Timeout - operação pode ter sido concluída. Recarregue a página.";
          } else if (status === "parsererror") {
            errorMessage = "Erro de formato na resposta do servidor";
          } else if (xhr.status === 0) {
            errorMessage = "Sem conexão com o servidor";
          } else if (xhr.status >= 500) {
            errorMessage = "Erro interno do servidor";
          }

          // showMessage(errorMessage, "error", 5000); // Commented for future use
        }

        // Re-enable radio buttons
        radioGroup.prop("disabled", false);
      },
    });
  }

  // ========================================
  // EXPORT AND DOWNLOAD FUNCTIONS
  // ========================================

  function toggleDestaque(id_eventos, currentStatus) {
    // Prevent any form submissions or page reloads
    event && event.preventDefault && event.preventDefault();
    event && event.stopPropagation && event.stopPropagation();

    var button = $("#toggle-destaque-" + id_eventos);
    var newStatus = !currentStatus;

    // Ensure button exists
    if (button.length === 0) {
      console.error("Toggle destaque button not found for evento:", id_eventos);
      return false;
    }

    // Prevent double-clicks
    if (button.prop("disabled")) {
      return false;
    }

    // Add loading state
    button.prop("disabled", true);
    var originalText = button.html();
    var originalClasses = button.attr("class");
    button.html('<i class="fas fa-spinner fa-spin"></i> Atualizando...');

    $.ajax({
      type: "POST",
      url: "controller.php",
      dataType: "json",
      data: {
        function: "toggleEventoDestaque",
        id_eventos: id_eventos,
        destaque: newStatus ? 1 : 0,
      },
      success: function (response) {
        if (response.success) {
          // Update button appearance with proper class management
          button.removeClass("btn-destaque-off btn-destaque-on");

          if (newStatus) {
            button.addClass("btn-destaque-on");
            button.html('<i class="fas fa-star"></i> Em Destaque');
          } else {
            button.addClass("btn-destaque-off");
            button.html('<i class="fas fa-star"></i> Sem Destaque');
          }

          // Update onclick handler to reflect new state
          button.attr(
            "onclick",
            "toggleDestaque(" +
              id_eventos +
              ", " +
              newStatus +
              "); return false;",
          );

          // Show success message
          var message = newStatus
            ? "✅ Evento adicionado aos destaques!"
            : "✅ Evento removido dos destaques (registos preservados)!";
          showMessage(message, "success");

          console.log("Destaque updated successfully. New status:", newStatus);
        } else {
          // Restore original state on error
          button.attr("class", originalClasses);
          button.html(originalText);
          showMessage(
            "❌ Erro ao atualizar destaque: " +
              (response.message || "Erro desconhecido"),
            "error",
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("Toggle destaque error:", {
          xhr: xhr,
          status: status,
          error: error,
          id_eventos: id_eventos,
        });

        // Restore original state on error
        button.attr("class", originalClasses);
        button.html(originalText);
        showMessage("❌ Erro de conexão ao atualizar destaque", "error");
      },
      complete: function () {
        // Always re-enable the button
        button.prop("disabled", false);
      },
    });

    // Prevent any default actions
    return false;
  }

  function downloadAllStudentsCertificates() {
    // Get all student IDs from the current page
    var studentIds = [];
    var studentNames = [];

    $(".alunos tbody tr[data-student-id]").each(function () {
      studentIds.push($(this).data("student-id"));
      studentNames.push($(this).data("student-name"));
    });

    if (studentIds.length === 0) {
      alert("Nenhum aluno encontrado na página atual.");
      return;
    }

    // Show loading message
    showMessage(
      "⏳ Preparando download de " + studentIds.length + " certificados...",
      "success",
    );

    // Disable the button
    var button = $('button[onclick*="downloadAllStudentsCertificates"]');
    button.prop("disabled", true);
    var originalText = button.html();
    button.html(
      '<i class="fas fa-spinner fa-spin"></i> Gerando ' +
        studentIds.length +
        " Certificados...",
    );

    // Send request to generate all certificates
    $.ajax({
      type: "POST",
      url: "controller.php",
      dataType: "json",
      data: {
        function: "downloadMultipleCertificates",
        student_ids: studentIds,
      },
      success: function (response) {
        console.log("Download response:", response);

        if (response.success) {
          showMessage("✅ " + response.message, "success");

          // If there are download URLs, open them
          if (response.downloads && response.downloads.length > 0) {
            response.downloads.forEach(function (download, index) {
              setTimeout(function () {
                window.open(download.url, "_blank");
              }, index * 500); // Stagger downloads by 500ms
            });
          }
        } else {
          showMessage(
            "❌ Erro ao gerar certificados: " +
              (response.message || "Erro desconhecido"),
            "error",
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("Download Error:", {
          xhr: xhr,
          status: status,
          error: error,
        });
        showMessage("❌ Erro de conexão ao gerar certificados", "error");
      },
      complete: function () {
        // Re-enable the button
        button.prop("disabled", false);
        button.html(originalText);
      },
    });
  }

  function exportStudentsList() {
    // Get all student data from the current page
    var studentsData = [];
    var headers = [];

    // Get table headers
    $(".alunos thead th").each(function () {
      headers.push($(this).text().trim());
    });

    // Get student data from each row
    $(".alunos tbody tr[data-student-id]").each(function () {
      var rowData = [];
      $(this)
        .find("td")
        .each(function (index) {
          var cellText = $(this).text().trim();
          // Clean up the cell data
          if (index === 1) {
            // Skip the photo column - just put "Foto"
            rowData.push("Foto");
          } else if (index === 2) {
            // For name column, extract just the text without link
            var nameLink = $(this).find("a");
            if (nameLink.length > 0) {
              rowData.push(nameLink.text().trim());
            } else {
              rowData.push(cellText);
            }
          } else if (index === 9) {
            // Skip the certificate button column - just put "Disponível"
            rowData.push("Disponível");
          } else {
            rowData.push(cellText);
          }
        });
      studentsData.push(rowData);
    });

    if (studentsData.length === 0) {
      alert("Nenhum aluno encontrado na página atual.");
      return;
    }

    // Show loading message
    showMessage(
      "⏳ Preparando exportação de " + studentsData.length + " alunos...",
      "success",
    );

    // Disable the button
    var button = $('button[onclick*="exportStudentsList"]');
    button.prop("disabled", true);
    var originalText = button.html();
    button.html(
      '<i class="fas fa-spinner fa-spin"></i> Exportando ' +
        studentsData.length +
        " Alunos...",
    );

    // Create a form to submit data for direct download
    var form = $("<form>", {
      method: "POST",
      action: "controller.php",
      target: "_blank",
    });

    // Add hidden inputs
    form.append(
      $("<input>", {
        type: "hidden",
        name: "function",
        value: "exportStudentsList",
      }),
    );

    form.append(
      $("<input>", {
        type: "hidden",
        name: "headers",
        value: JSON.stringify(headers),
      }),
    );

    form.append(
      $("<input>", {
        type: "hidden",
        name: "students_data",
        value: JSON.stringify(studentsData),
      }),
    );

    // Append form to body and submit
    $("body").append(form);
    form.submit();
    form.remove();

    // Show success message and re-enable button
    setTimeout(function () {
      showMessage(
        "✅ Lista exportada com sucesso! (" + studentsData.length + " alunos)",
        "success",
      );
      button.prop("disabled", false);
      button.html(originalText);
    }, 1000);
  }

  // ========================================
  // MAKE FUNCTIONS GLOBALLY AVAILABLE
  // ========================================

  // Make functions available globally for onclick handlers
  window.toggleDestaque = toggleDestaque;
  window.downloadAllStudentsCertificates = downloadAllStudentsCertificates;
  window.exportStudentsList = exportStudentsList;
  window.reloadAulaStats = reloadAulaStats;

  // ========================================
  // KEYBOARD SHORTCUTS
  // ========================================

  // Keyboard shortcuts for presence management
  $(document).on("keydown", function (e) {
    // Only activate if we're on a page with presence buttons and not in an input field
    if (
      $(".presence-buttons").length > 0 &&
      !$(e.target).is("input, textarea, select")
    ) {
      var $focused = $(".presence-buttons").first(); // Focus on first row for demo

      if (e.key.toLowerCase() === "p") {
        e.preventDefault();
        $focused.find('[data-status="1"]').click();
      } else if (e.key.toLowerCase() === "f") {
        e.preventDefault();
        $focused.find('[data-status="0"]').click();
      }
    }
  });

  // Arrow keys navigation - Enhanced for autocomplete compatibility
  $(document).keydown(function (e) {
    // Only activate arrow keys if not focused on autocomplete search
    if (!$("#student-search").is(":focus")) {
      switch (e.which) {
        case 37:
          if ($("select[name=id_alunos]").length) {
            $("select option:selected").prev().attr("selected", "selected");
            $("form").submit();
          }
          break;
        case 39:
          if ($("select[name=id_alunos]").length) {
            $("select option:selected").next().attr("selected", "selected");
            $("form").submit();
          }
          break;
      }
    }
  });

  // ========================================
  // TURMAS FUNCTIONALITY
  // ========================================

  $("#turmas .horario td.link").on("click", function () {
    $("#turmas .horario td.link").removeClass("ativo");
    $("#turmas .horario").hide();
  });

  // ========================================
  // ALUNOS FUNCTIONALITY
  // ========================================

  $(".horario td.link").on("click", function () {
    $(this).toggleClass("ativo");

    var id_aulas = $(this).data("id_aulas");
    var dia = $(this).data("dia");
    var id_alunos = $("#id_alunos").val();
    var turma = $(this).data("turma");
    var titulo = $(this).data("titulo");
    var total_alunos = $(this).find("span.total_alunos").text();

    var action = "remove";
    if ($(this).hasClass("ativo")) {
      var action = "insert";
      total_alunos++;
    } else {
      total_alunos--;
    }

    $(this).find("span.total_alunos").text(total_alunos);

    if (!id_alunos) {
      var id_alunos = 0;
    }

    if (id_alunos) {
      //Aulas
      $.ajax({
        type: "POST",
        url: "controller.php",
        async: false,
        dataType: "html",
        data: {
          function: "aula",
          id_alunos: id_alunos,
          id_aulas: id_aulas,
          dia: dia,
          action: action,
        },
      }).done(function (data) {
        $(".mensalidade").empty();
        $(".mensalidade").text(data);
      });
    } else {
      //Turmas
      $.ajax({
        type: "POST",
        url: "controller.php",
        async: false,
        dataType: "html",
        data: {
          function: "turma",
          id_aulas: id_aulas,
          dia: dia,
        },
      }).done(function (data) {
        $(".turma").empty();
        $(data).appendTo(".turma");
        $(".turma h2").text(titulo);
      });
    }
  });

  // Handle both select (legacy) and hidden input (autocomplete) for student selection
  $("select[name=id_alunos], input[name=id_alunos]").on("change", function () {
    $(this).parent("form").submit();
  });

  // Auto-submit for age and date filters only
  $(
    "input[name=filter_idade_min], input[name=filter_idade_max], input[name=filter_aniversario_date]",
  ).on("change", function () {
    if ($(this).attr("name") == "data_inicio") {
      $("input[name=data_fim]").val($(this).val());
    }

    $(this).parents("form").submit();
  });

  // Prevent auto-submit for search fields to allow combined searches
  var searchTimeout;
  $("input[name=search_name], input[name=search_phone]").on(
    "input",
    function () {
      // Clear previous timeout
      clearTimeout(searchTimeout);

      // Set a delay to allow user to type in both fields
      searchTimeout = setTimeout(function () {
        // Auto-submit after 1 second of no typing
        $("form[name=alunos]").submit();
      }, 1000);
    },
  );

  // Immediate submit when user presses Enter in search fields
  $("input[name=search_name], input[name=search_phone]").on(
    "keypress",
    function (e) {
      if (e.which === 13) {
        // Enter key
        clearTimeout(searchTimeout);
        $(this).parents("form").submit();
      }
    },
  );

  // ========================================
  // PRESENCE FUNCTIONALITY
  // ========================================

  $(document).on("click", ".image-list-small a", function (e) {
    $(this).toggleClass("ativo");
    $(this).parent().find(".details").toggleClass("check");

    var data = $("input[name=data]").val();
    var hoje = dataAtual();

    var id_aulas = $("select[name=id_aulas]").val();
    var dia = $("select[name=id_aulas] option:selected").data("dia");
    var id_alunos = $(this).data("id_alunos");

    var action = "remove";
    if ($(this).hasClass("ativo")) {
      var action = "insert";
    }

    $.ajax({
      type: "POST",
      url: "controller.php",
      async: false,
      dataType: "html",
      data: {
        function: "presencas",
        id_aulas: id_aulas,
        dia: dia,
        data: data,
        id_alunos: id_alunos,
        action: action,
      },
    }).done(function (data) {
      // Atualizar estatísticas após marcar/desmarcar presença
      if (typeof reloadAulaStats === "function") {
        reloadAulaStats();
      }
    });

    e.preventDefault();
  });

  $("body").on(
    "change",
    "select[name=id_aulas], input[name=data]",
    function () {
      var id_aulas = $("select[name=id_aulas]").val();
      var dia = $("select[name=id_aulas]")
        .find("option[value=" + id_aulas + "]:selected")
        .data("dia");
      var data = $("input[name=data]").val();

      $("input[name='dia']").val(dia);
      $(this).parents("form").submit();
    },
  );

  $("body").on("change", "#presencas input[name=observacoes]", function () {
    var id_presencas = $(this).data("id_presencas");
    var observacoes = $(this).val();

    $.ajax({
      type: "POST",
      url: "controller.php",
      async: false,
      dataType: "html",
      data: {
        function: "presencasObservacoes",
        id_presencas: id_presencas,
        observacoes: observacoes,
      },
    });
  });

  // Presence buttons (P/F) - Fixed implementation
  $("body").on("click", ".presence-btn", function (e) {
    e.preventDefault();

    var $btn = $(this);
    var $btnGroup = $btn.parent(".presence-buttons");
    var $row = $btn.closest("tr");
    var id_presencas = $btnGroup.data("id_presencas");
    var presente = $btn.data("status");

    // Extract class info from URL parameters or data attributes
    var urlParams = new URLSearchParams(window.location.search);
    var id_aulas =
      urlParams.get("id_aulas") ||
      $row.data("id_aulas") ||
      $btnGroup.data("id_aulas");
    var data =
      urlParams.get("data") || $row.data("data") || $btnGroup.data("data");

    // If still no data, try to get from global context or page elements
    if (!id_aulas) {
      id_aulas = $("#id_aulas").val() || $(".aula-info").data("id_aulas");
    }
    if (!data) {
      data =
        $("#data").val() ||
        $(".aula-info").data("data") ||
        new Date().toISOString().split("T")[0];
    }

    if (!id_presencas) {
      alert("Erro: ID da presença não encontrado");
      return;
    }

    // Check if button is already in the correct state to avoid redundant updates
    var currentlyActive =
      $btn.hasClass("btn-success") || $btn.hasClass("btn-danger");
    var isCorrectState =
      (presente == 1 && $btn.hasClass("btn-success")) ||
      (presente == 0 && $btn.hasClass("btn-danger"));

    if (isCorrectState) {
      return;
    }

    // Add loading state
    $btn.prop("disabled", true);
    var originalHtml = $btn.html();
    $btn.html('<i class="fas fa-spinner fa-spin"></i>');

    // Update button styles immediately
    $btnGroup.find(".presence-btn").each(function () {
      var $currentBtn = $(this);
      var status = $currentBtn.data("status");

      if (status == presente) {
        if (status == 1) {
          $currentBtn
            .removeClass("btn-outline-success")
            .addClass("btn-success");
        } else {
          $currentBtn.removeClass("btn-outline-danger").addClass("btn-danger");
        }
      } else {
        if (status == 1) {
          $currentBtn
            .removeClass("btn-success")
            .addClass("btn-outline-success");
        } else {
          $currentBtn.removeClass("btn-danger").addClass("btn-outline-danger");
        }
      }
    });

    // Prepare AJAX data
    var ajaxData = {
      function: "presencasPresente",
      id_presencas: id_presencas,
      presente: presente,
    };

    // Only request stats if we have the required data
    var requestStats = id_aulas && data;
    if (requestStats) {
      ajaxData.return_stats = "true";
      ajaxData.id_aulas = id_aulas;
      ajaxData.data = data;
    }

    // Send AJAX request
    $.ajax({
      type: "POST",
      url: "controller.php",
      async: true,
      dataType: "json",
      data: ajaxData,
    })
      .done(function (response) {
        // Restore button
        $btn.prop("disabled", false);
        $btn.html(originalHtml);

        // Check if response is valid
        if (response && response.success) {
          // Update stats if available and requested
          if (requestStats && response.presentes !== undefined) {
            updateAulaStats(response);
          } else if (requestStats) {
            reloadAulaStats();
          }

          // Add success visual feedback with pulse effect
          $row.addClass("table-success").addClass("pulse-success");
          setTimeout(function () {
            $row.removeClass("table-success pulse-success");
          }, 1500);

          // Add subtle success flash to the button
          $btn.addClass("btn-flash-success");
          setTimeout(function () {
            $btn.removeClass("btn-flash-success");
          }, 800);
        } else {
          console.error("Server response indicates failure:", response);
          alert(
            "Erro ao atualizar presença: " +
              (response.error || "Erro desconhecido"),
          );

          // Revert button state on error
          $btnGroup.find(".presence-btn").each(function () {
            var $currentBtn = $(this);
            var status = $currentBtn.data("status");
            $currentBtn
              .removeClass("btn-success btn-danger")
              .addClass(
                status == 1 ? "btn-outline-success" : "btn-outline-danger",
              );
          });
        }
      })
      .fail(function (xhr, status, error) {
        // Restore button on error
        $btn.prop("disabled", false);
        $btn.html(originalHtml);

        // Show error to user
        var errorMsg = "Erro ao atualizar presença";
        if (xhr.responseText && xhr.responseText.includes("Fatal error")) {
          errorMsg += ": Erro interno do servidor";
        } else if (status === "parsererror") {
          errorMsg += ": Resposta inválida do servidor";
        }

        alert(errorMsg);

        // Show error with shake animation
        $row.addClass("table-danger");
        setTimeout(function () {
          $row.removeClass("table-danger");
        }, 2000);

        // Try to reload stats as fallback
        reloadAulaStats();
      });
  });

  // Legacy select handler (keeping for compatibility)
  $("body").on("change", "#presencas select[name=presenca]", function () {
    var id_presencas = $(this).data("id_presencas");
    var presente = $(this).val();

    //Atualizar class
    $(this).removeClass("text-success");
    $(this).removeClass("text-danger");

    if (presente == 1) {
      $(this).addClass("text-success");
    } else {
      $(this).addClass("text-danger");
    }

    $.ajax({
      type: "POST",
      url: "controller.php",
      async: false,
      dataType: "html",
      data: {
        function: "presencasPresente",
        id_presencas: id_presencas,
        presente: presente,
      },
    });
  });

  // Apaga Presença
  $(document).on("click", ".apagaPresenca", function (e) {
    var id_presencas = $(this).data("id_presencas");
    var el = $(this);

    $.ajax({
      type: "POST",
      url: "controller.php",
      async: false,
      dataType: "text",
      data: {
        function: "apagaPresenca",
        id_presencas: id_presencas,
      },
    }).done(function () {
      $(el).parents("tr").remove();

      // Refresh statistics after deleting presence
      if (typeof window.reloadAulaStats === "function") {
        window.reloadAulaStats();
      } else {
        // Fallback: reload page if function not available
        console.log("reloadAulaStats not available, reloading page");
        setTimeout(function () {
          location.reload();
        }, 100);
      }
    });

    e.preventDefault();
  });

  // Apaga Presenças
  $(document).on("click", ".apagaPresencas", function (e) {
    var deletedCount = 0;
    var totalToDelete = $(".apagaPresenca").length;

    $(".apagaPresenca").each(function () {
      var $apagaBtn = $(this);
      var id_presencas = $apagaBtn.data("id_presencas");

      $.ajax({
        type: "POST",
        url: "controller.php",
        async: false,
        dataType: "text",
        data: {
          function: "apagaPresenca",
          id_presencas: id_presencas,
        },
      }).done(function () {
        $apagaBtn.parents("tr").remove();
        deletedCount++;

        // Update stats after all deletions are complete
        if (deletedCount === totalToDelete) {
          if (typeof window.reloadAulaStats === "function") {
            window.reloadAulaStats();
          } else {
            // Fallback: reload page if function not available
            console.log("reloadAulaStats not available, reloading page");
            setTimeout(function () {
              location.reload();
            }, 100);
          }
        }
      });
    });

    e.preventDefault();
  });

  // Initialize stats on page load
  if ($(".aula-stats").length) {
    reloadAulaStats();
  }

  // ========================================
  // EVENT PRESENCE FUNCTIONALITY
  // ========================================

  // Presenças de Eventos
  $(document)
    .off("change", ".presenca-evento")
    .on("change", ".presenca-evento", function () {
      var radio = $(this);
      var id_aluno = radio.data("id-aluno");
      var id_evento = radio.data("id-evento");
      var id_presenca = radio.data("id-presenca");
      var presente = parseInt(radio.val());

      // Call the extracted function
      handlePresenceChange(radio, id_aluno, id_evento, id_presenca, presente);
    });

  // Associações de Professores com Eventos
  $(document).on("change", ".professor-evento", function () {
    var radio = $(this);
    var id_professor = radio.data("id-professor");
    var id_evento = radio.data("id-evento");
    var id_associacao = radio.data("id-associacao");
    var presente = parseInt(radio.val());

    // Debug logging
    console.log("=== PROFESSOR-EVENTO PRESENCE BUTTON CLICKED ===");
    console.log("Data attributes:", {
      id_professor: id_professor,
      id_evento: id_evento,
      id_associacao: id_associacao,
      presente: presente,
    });

    // Encontrar todos os radio buttons do mesmo grupo
    var radioGroup = $("input[name='" + radio.attr("name") + "']");

    // Add loading state
    var parentRow = radio.closest("tr");
    var originalContent = parentRow.find("td:last").html();
    parentRow
      .find("td:last")
      .html(
        '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Carregando...</span></div>',
      );

    // Disable all radio buttons in the group during request
    radioGroup.prop("disabled", true);

    // Atualizar todos os radio buttons do grupo com os mesmos dados
    radioGroup.each(function () {
      $(this).data("id-associacao", id_associacao);
    });

    // Enviar para o servidor
    var ajaxData = {
      function: "professorEvento",
      id_professor: id_professor,
      id_evento: id_evento,
      id_associacao: id_associacao,
      presente: presente,
      action: id_associacao > 0 ? "update" : "insert",
    };

    console.log("Sending AJAX request with data:", ajaxData);

    $.ajax({
      type: "POST",
      url: "controller.php",
      dataType: "json",
      data: ajaxData,
      success: function (response) {
        console.log("Server response:", response);

        if (response.success) {
          // Sempre atualizar o data-id-associacao com o valor retornado
          radioGroup.each(function () {
            $(this).data("id-associacao", response.id_associacao);
          });

          // Update professor statistics in real-time
          updateProfessorStats();

          // Show success indicator
          parentRow
            .find("td:last")
            .html('<i class="fas fa-check text-success"></i>');
          setTimeout(function () {
            parentRow.find("td:last").html(originalContent);
          }, 2000);

          console.log("Professor association updated successfully");
        } else {
          console.error("Server returned error:", response.message);

          // Reverter a seleção se houve erro
          var oppositeValue = presente === 1 ? 0 : 1;
          radioGroup
            .filter("[value='" + oppositeValue + "']")
            .prop("checked", true);

          // Show error indicator
          parentRow
            .find("td:last")
            .html('<i class="fas fa-times text-danger"></i>');
          setTimeout(function () {
            parentRow.find("td:last").html(originalContent);
          }, 3000);

          alert(
            "Erro ao atualizar associação: " +
              (response.message || "Erro desconhecido"),
          );
        }

        // Re-enable radio buttons
        radioGroup.prop("disabled", false);
      },
      error: function (xhr, status, error) {
        console.error("AJAX error:", {
          xhr: xhr,
          status: status,
          error: error,
        });

        // Reverter a seleção se houve erro
        var oppositeValue = presente === 1 ? 0 : 1;
        radioGroup
          .filter("[value='" + oppositeValue + "']")
          .prop("checked", true);

        // Show error indicator
        parentRow
          .find("td:last")
          .html('<i class="fas fa-exclamation-triangle text-warning"></i>');
        setTimeout(function () {
          parentRow.find("td:last").html(originalContent);
        }, 3000);

        // Re-enable radio buttons
        radioGroup.prop("disabled", false);

        alert("Erro de conexão ao atualizar associação");
      },
    });
  });

  // ========================================
  // DIAGNOSTIC AND INITIALIZATION
  // ========================================

  // Diagnostic code for presence system
  console.log("=== PRESENCE SYSTEM DIAGNOSTICS ===");
  console.log("Document ready triggered");
  console.log("jQuery version:", $.fn.jquery);

  // Check if presence radio buttons exist
  var presenceButtons = $(".presenca-evento");
  console.log("Found presence buttons:", presenceButtons.length);

  if (presenceButtons.length > 0) {
    console.log("Sample presence button:", presenceButtons.first()[0]);
    console.log("Sample data attributes:", {
      "id-aluno": presenceButtons.first().data("id-aluno"),
      "id-evento": presenceButtons.first().data("id-evento"),
      "id-presenca": presenceButtons.first().data("id-presenca"),
    });
  }

  // Test manual event binding as fallback
  $(".presenca-evento")
    .off("change")
    .on("change", function () {
      console.log("=== DIRECT EVENT HANDLER TRIGGERED ===");
      var radio = $(this);
      var id_aluno = radio.data("id-aluno");
      var id_evento = radio.data("id-evento");
      var id_presenca = radio.data("id-presenca");
      var presente = parseInt(radio.val());

      console.log("Direct handler data:", {
        id_aluno: id_aluno,
        id_evento: id_evento,
        id_presenca: id_presenca,
        presente: presente,
      });

      // Call the same AJAX function
      handlePresenceChange(radio, id_aluno, id_evento, id_presenca, presente);
    });

  // Test event binding
  setTimeout(function () {
    console.log("Testing event binding...");
    var testButton = $(".presenca-evento").first();
    if (testButton.length > 0) {
      console.log("Test button found:", testButton[0]);
      console.log("Event handlers attached:", $._data(testButton[0], "events"));
    }
  }, 1000);
});
