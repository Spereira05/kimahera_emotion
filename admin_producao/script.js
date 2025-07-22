$(function()
{
    // Initialize Bootstrap tooltips for presence buttons
    $('[title]').tooltip();
    
    // Keyboard shortcuts for presence management
    $(document).on('keydown', function(e) {
        // Only activate if we're on a page with presence buttons and not in an input field
        if ($('.presence-buttons').length > 0 && !$(e.target).is('input, textarea, select')) {
            var $focused = $('.presence-buttons').first(); // Focus on first row for demo
            
            if (e.key.toLowerCase() === 'p') {
                e.preventDefault();
                $focused.find('[data-status="1"]').click();
            } else if (e.key.toLowerCase() === 'f') {
                e.preventDefault();
                $focused.find('[data-status="0"]').click();
            }
        }
    });
    
    //Turmas
    $('#turmas .horario td.link').on('click', function()
    {
        $('#turmas .horario td.link').removeClass('ativo');
        $('#turmas .horario').hide();
    });
    ///Turmas

    //Alunos
    $('.horario td.link').on('click', function()
    {
        $(this).toggleClass('ativo');
        
        var id_aulas = $(this).data('id_aulas');
        var dia = $(this).data('dia');
        var id_alunos = $('#id_alunos').val();
        var turma = $(this).data('turma');
        var titulo = $(this).data('titulo');
        var total_alunos = $(this).find('span.total_alunos').text();

        var action = 'remove';
        if($(this).hasClass('ativo'))
        {
            var action = 'insert';
            total_alunos++;
        }
        else
        {
            total_alunos--;
        }

        $(this).find('span.total_alunos').text(total_alunos);

        if(!id_alunos){var id_alunos = 0;}
        
        if(id_alunos)
        {
            //Aulas
            $.ajax
            ({
                type: 'POST',
                url: 'controller.php',
                async: false,
                dataType: 'html',
                data:
                {
                    'function': 'aula',
                    'id_alunos': id_alunos,
                    'id_aulas': id_aulas,
                    'dia': dia,
                    'action': action
                }
            }).done(function(data)
            {
                $('.mensalidade').empty();
                $('.mensalidade').text(data);
            });
            ///Aulas
        }
        else
        {
            //Turmas
            $.ajax
            ({
                type: 'POST',
                url: 'controller.php',
                async: false,
                dataType: 'html',
                data: 
                {
                    'function': 'turma',
                    'id_aulas': id_aulas,
                    'dia': dia
                }
            }).done(function(data)
            {
                $('.turma').empty();
                $(data).appendTo('.turma');
                $('.turma h2').text(titulo);
            });
            ///Turmas
        }
    });

    $('select[name=id_alunos]').on('change', function()
    {
        $(this).parent('form').submit();
    });

    $('select[name=id_professores], select[name=id_modalidades], input[name=data_inicio], input[name=data_fim]').on('change', function()
    {
        if($(this).attr('name') == 'data_inicio')
        {
            $('input[name=data_fim]').val($(this).val());
        }

        $(this).parents('form').submit();
    });

    //Setas
    $(document).keydown(function(e)
    {
        switch(e.which)
        {
            case 37:
                $('select option:selected').prev().attr('selected', 'selected');
                $('form').submit();
            break;
            case 39:
                $('select option:selected').next().attr('selected', 'selected');
                $('form').submit();
            break;
        }
    });
    ///Setas
    ///Alunos

    $(document).on('click', '.image-list-small a', function(e)
    {
        $(this).toggleClass('ativo');
        $(this).parent().find('.details').toggleClass('check');

        var data = $('input[name=data]').val();
        var hoje = dataAtual();

        var id_aulas = $('select[name=id_aulas]').val();
        var dia = $('select[name=id_aulas] option:selected').data('dia');
        var id_alunos = $(this).data('id_alunos');

        var action = 'remove';
        if($(this).hasClass('ativo'))
        {
            var action = 'insert';
        }

        $.ajax
        ({
            type: 'POST',
            url: 'controller.php',
            async: false,
            dataType: 'html',
            data: 
            {
                'function': 'presencas',
                'id_aulas': id_aulas,
                'dia': dia,
                'data': data,
                'id_alunos': id_alunos,
                'action': action
            }
        }).done(function(data)
        {
            // Atualizar estatísticas após marcar/desmarcar presença
            if (typeof reloadAulaStats === 'function') {
                reloadAulaStats();
            }
        });

        e.preventDefault();
    });

    //Turmas
    $('body').on('change', 'select[name=id_aulas], input[name=data]', function()
    {
        var id_aulas = $('select[name=id_aulas]').val();
        var dia = $('select[name=id_aulas]').find('option[value='+id_aulas+']:selected').data('dia');
        var data = $('input[name=data]').val();

        $("input[name='dia']").val(dia);
        $(this).parents('form').submit();
    });
    ///Turmas

    function dataAtual()
    {
        var hoje = '';

        $.ajax
        ({
            type: 'POST',
            url: 'controller.php',
            async: false,
            dataType: 'html',
            data: 
            {
                'function': 'dataAtual'
            }
        }).done(function(data)
        {
            hoje = data;
        });

        return hoje;
    }

    //Presenças
    $('body').on('change', '#presencas input[name=observacoes]', function()
    {        
        var id_presencas = $(this).data('id_presencas');
        var observacoes = $(this).val();

        $.ajax
        ({
            type: 'POST',
            url: 'controller.php',
            async: false,
            dataType: 'html',
            data: 
            {
                'function': 'presencasObservacoes',
                'id_presencas': id_presencas,
                'observacoes': observacoes
            }
        });
    });

    // Presence buttons (P/F) - Fixed implementation
    $('body').on('click', '.presence-btn', function(e)
    {
        e.preventDefault();
        
        var $btn = $(this);
        var $btnGroup = $btn.parent('.presence-buttons');
        var $row = $btn.closest('tr');
        var id_presencas = $btnGroup.data('id_presencas');
        var presente = $btn.data('status');
        
        // Extract class info from URL parameters or data attributes
        var urlParams = new URLSearchParams(window.location.search);
        var id_aulas = urlParams.get('id_aulas') || $row.data('id_aulas') || $btnGroup.data('id_aulas');
        var data = urlParams.get('data') || $row.data('data') || $btnGroup.data('data');
        
        // If still no data, try to get from global context or page elements
        if (!id_aulas) {
            id_aulas = $('#id_aulas').val() || $('.aula-info').data('id_aulas');
        }
        if (!data) {
            data = $('#data').val() || $('.aula-info').data('data') || new Date().toISOString().split('T')[0];
        }
        
        if (!id_presencas) {
            alert('Erro: ID da presença não encontrado');
            return;
        }
        
        if (!id_aulas || !data) {
            // Will update presence without stats if data is missing
        }
        
        // Check if button is already in the correct state to avoid redundant updates
        var currentlyActive = $btn.hasClass('btn-success') || $btn.hasClass('btn-danger');
        var isCorrectState = (presente == 1 && $btn.hasClass('btn-success')) || 
                            (presente == 0 && $btn.hasClass('btn-danger'));
        
        if (isCorrectState) {
            return;
        }
        
        // Add loading state
        $btn.prop('disabled', true);
        var originalHtml = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin"></i>');
        
        // Update button styles immediately
        $btnGroup.find('.presence-btn').each(function() {
            var $currentBtn = $(this);
            var status = $currentBtn.data('status');
            
            if (status == presente) {
                if (status == 1) {
                    $currentBtn.removeClass('btn-outline-success').addClass('btn-success');
                } else {
                    $currentBtn.removeClass('btn-outline-danger').addClass('btn-danger');
                }
            } else {
                if (status == 1) {
                    $currentBtn.removeClass('btn-success').addClass('btn-outline-success');
                } else {
                    $currentBtn.removeClass('btn-danger').addClass('btn-outline-danger');
                }
            }
        });
        
        // Prepare AJAX data
        var ajaxData = {
            'function': 'presencasPresente',
            'id_presencas': id_presencas,
            'presente': presente
        };
        
        // Only request stats if we have the required data
        var requestStats = (id_aulas && data);
        if (requestStats) {
            ajaxData.return_stats = 'true';
            ajaxData.id_aulas = id_aulas;
            ajaxData.data = data;
        }
        
        // Send AJAX request
        $.ajax({
            type: 'POST',
            url: 'controller.php',
            async: true,
            dataType: 'json',
            data: ajaxData
        }).done(function(response) {
            // Restore button
            $btn.prop('disabled', false);
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
                $row.addClass('table-success').addClass('pulse-success');
                setTimeout(function() {
                    $row.removeClass('table-success pulse-success');
                }, 1500);
                
                // Add subtle success flash to the button
                $btn.addClass('btn-flash-success');
                setTimeout(function() {
                    $btn.removeClass('btn-flash-success');
                }, 800);
                
            } else {
                console.error('Server response indicates failure:', response);
                alert('Erro ao atualizar presença: ' + (response.error || 'Erro desconhecido'));
                
                // Revert button state on error
                $otherButtons.removeClass('btn-success btn-danger').addClass(function() {
                    return $(this).data('status') == 1 ? 'btn-outline-success' : 'btn-outline-danger';
                });
                
                if (presente == 1) {
                    $btn.removeClass('btn-outline-success').addClass('btn-success');
                } else {
                    $btn.removeClass('btn-outline-danger').addClass('btn-danger');
                }
            }
        }).fail(function(xhr, status, error) {
            // Restore button on error
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
            
            // Show error to user
            var errorMsg = 'Erro ao atualizar presença';
            if (xhr.responseText && xhr.responseText.includes('Fatal error')) {
                errorMsg += ': Erro interno do servidor';
            } else if (status === 'parsererror') {
                errorMsg += ': Resposta inválida do servidor';
            }
            
            alert(errorMsg);
            
            // Show error with shake animation
            $row.addClass('table-danger');
            setTimeout(function() {
                $row.removeClass('table-danger');
            }, 2000);
            
            // Try to reload stats as fallback
            reloadAulaStats();
        });
    });

    // Function to update statistics in the UI
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

    // Function to reload full statistics
    function reloadAulaStats() {
        var urlParams = new URLSearchParams(window.location.search);
        var id_aulas = urlParams.get('id_aulas');
        var data = urlParams.get('data');
        
        if (id_aulas && data) {
            $.ajax({
                type: 'POST',
                url: 'controller.php',
                async: true,
                dataType: 'json',
                data: {
                    'function': 'getAulaStats',
                    'id_aulas': id_aulas,
                    'data': data
                }
            }).done(function(response) {
                if (response && response.presentes !== undefined) {
                    updateAulaStats(response);
                }
            }).fail(function(xhr, status, error) {
                console.error('Stats reload error:', status, error);
            });
        }
    }
    
    // Make reloadAulaStats available globally
    window.reloadAulaStats = reloadAulaStats;

    // Legacy select handler (keeping for compatibility)
    $('body').on('change', '#presencas select[name=presenca]', function()
    {
        var id_presencas = $(this).data('id_presencas');
        var presente = $(this).val();

        //Atualizar class
        $(this).removeClass('text-success');
        $(this).removeClass('text-danger');

        if(presente == 1)
        {
            $(this).addClass('text-success');
        }
        else
        {
            $(this).addClass('text-danger');
        }
        ///Atualizar class

        $.ajax
        ({
            type: 'POST',
            url: 'controller.php',
            async: false,
            dataType: 'html',
            data: 
            {
                'function': 'presencasPresente',
                'id_presencas': id_presencas,
                'presente': presente
            }
        });
    });

    //Apaga Presença
    $(document).on('click', '.apagaPresenca', function(e)
    {
        var id_presencas = $(this).data('id_presencas');
        var el = $(this);

        $.ajax
        ({
            type: 'POST',
            url: 'controller.php',
            async: false,
            dataType: 'text',
            data: 
            {
                'function': 'apagaPresenca',
                'id_presencas': id_presencas
            }
        }).done(function()
        {
            $(el).parents('tr').remove();
            
            // Refresh statistics after deleting presence
            if (typeof window.reloadAulaStats === 'function') {
                window.reloadAulaStats();
            } else {
                // Fallback: reload page if function not available
                console.log('reloadAulaStats not available, reloading page');
                setTimeout(function() {
                    location.reload();
                }, 100);
            }
        });
        
        e.preventDefault();
    });
    ///Apaga Presença

    //Apaga Presenças
    $(document).on('click', '.apagaPresencas', function(e)
    {
        var deletedCount = 0;
        var totalToDelete = $('.apagaPresenca').length;
        
        $('.apagaPresenca').each(function()
        {
            var $apagaBtn = $(this);
            var id_presencas = $apagaBtn.data('id_presencas');
            
            $.ajax
            ({
                type: 'POST',
                url: 'controller.php',
                async: false,
                dataType: 'text',
                data: 
                {
                    'function': 'apagaPresenca',
                    'id_presencas': id_presencas
                }
            }).done(function()
            {
                $apagaBtn.parents('tr').remove();
                deletedCount++;
                
                // Update stats after all deletions are complete
                if (deletedCount === totalToDelete) {
                    if (typeof window.reloadAulaStats === 'function') {
                        window.reloadAulaStats();
                    } else {
                        // Fallback: reload page if function not available
                        console.log('reloadAulaStats not available, reloading page');
                        setTimeout(function() {
                            location.reload();
                        }, 100);
                    }
                }
            });
        });

        e.preventDefault();
    });
    ///Apaga Presenças

    ///Presencas
    if ($('.aula-stats').length) {
        reloadAulaStats();
    }
    
    // Presenças de Eventos
    $(document).on("change", ".presenca-evento", function () {
      var radio = $(this);
      var id_aluno = radio.data("id-aluno");
      var id_evento = radio.data("id-evento");
      var id_presenca = radio.data("id-presenca");
      var presente = parseInt(radio.val());
      var now = new Date().toISOString().slice(0, 19).replace("T", " ");
  
      // Debug logging
      console.log("=== EVENTO PRESENCE BUTTON CLICKED ===");
      console.log("Element:", radio[0]);
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
  
      $.ajax({
        type: "POST",
        url: "controller.php",
        dataType: "json",
        data: ajaxData,
        success: function (response) {
          console.log("Server response:", response);
  
          if (response.success) {
            // Sempre atualizar o data-id-presenca com o valor retornado
            radioGroup.each(function () {
              $(this).data("id-presenca", response.id_presenca);
            });
  
            // Update presence statistics in real-time
            updatePresenceStats();
  
            console.log("Presence updated successfully");
          } else {
            console.error("Server returned error:", response.message);
  
            // Reverter a seleção se houve erro
            var oppositeValue = presente === 1 ? 0 : 1;
            radioGroup
              .filter("[value='" + oppositeValue + "']")
              .prop("checked", true);
            alert(
              "Erro ao atualizar presença: " +
                (response.message || "Erro desconhecido"),
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("AJAX error:", {
            xhr: xhr,
            status: status,
            error: error,
          });
        },
        error: function (xhr, status, error) {
          console.log("AJAX Error:");
          console.log("Status:", status);
          console.log("Error:", error);
          console.log("Response Text:", xhr.responseText);
  
          // Reverter a seleção se houve erro
          var oppositeValue = presente === 1 ? 0 : 1;
          radioGroup
            .filter("[value='" + oppositeValue + "']")
            .prop("checked", true);
          alert("Erro de conexão ao atualizar presença");
        },
      });
    });
    ///Presenças de Eventos
});

function toggleDestaque(id_eventos, currentStatus) {
  var button = $("#toggle-destaque-" + id_eventos);
  var newStatus = !currentStatus;

  // Add loading state
  button.prop("disabled", true);
  var originalText = button.html();
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
        // Update button appearance
        if (newStatus) {
          button.removeClass("btn-destaque-off").addClass("btn-destaque-on");
          button.html('<i class="fas fa-star"></i> Em Destaque');
        } else {
          button.removeClass("btn-destaque-on").addClass("btn-destaque-off");
          button.html('<i class="fas fa-star"></i> Sem Destaque');
        }

        // Update onclick handler
        button.attr(
          "onclick",
          "toggleDestaque(" + id_eventos + ", " + newStatus + ")",
        );

        // Show success message
        var message = newStatus
          ? "✅ Evento adicionado aos destaques!"
          : "✅ Evento removido dos destaques!";
        showMessage(message, "success");
      } else {
        button.html(originalText);
        showMessage(
          "❌ Erro ao atualizar destaque: " +
            (response.message || "Erro desconhecido"),
          "error",
        );
      }
    },
    error: function () {
      button.html(originalText);
      showMessage("❌ Erro de conexão ao atualizar destaque", "error");
    },
    complete: function () {
      button.prop("disabled", false);
    },
  });
}

// Function to show temporary messages
function showMessage(message, type) {
  // Remove existing messages
  $(".message-alert").remove();

  var alertClass = type === "success" ? "alert-success" : "alert-danger";
  var messageHtml =
    '<div class="alert ' +
    alertClass +
    ' message-alert alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 250px;">' +
    message +
    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
    "</div>";

  $("body").append(messageHtml);

  // Auto-hide after 3 seconds
  setTimeout(function () {
    $(".message-alert").fadeOut(500, function () {
      $(this).remove();
    });
  }, 3000);
}

// Function to download all certificates for students on current page
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