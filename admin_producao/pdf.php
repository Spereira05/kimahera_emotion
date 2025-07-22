<?php

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require "config.php";
require "model.php";
require "controller.php";
require "fpdf/fpdf.php";

$emotionModel = new EmotionModel();
$emotionController = new EmotionController();

// Validate student ID parameter
$id_alunos =
    isset($_GET["id_alunos"]) && is_numeric($_GET["id_alunos"])
        ? (int) $_GET["id_alunos"]
        : 0;

if ($id_alunos <= 0) {
    die("Error: Invalid or missing student ID parameter");
}

// Get student data
$aluno = $emotionController->aluno($id_alunos);
if (!$aluno) {
    die("Error: Student not found");
}

// Get student details
$result = $emotionModel->aluno($id_alunos);
$student_data = $result->fetch_array();

if (!$student_data) {
    die("Error: Student data not found");
}

$fontFamily = "SourceSansVariable-Roman";

// === CERTIFICATE VARIABLES ===

// 1. Ano Letivo
$ano_letivo = "2024/2025";
$certificado = "certificado2425";

// 2. Student name
$nome_aluno = $student_data["nome"];
$alcunha_aluno = $student_data["alcunha"] ?: "";

// Hard-coded modalidades abbreviations (only specific ones requested)
$modalidades_abbreviations = [
    "Contemporâneo II" => "Contemp. II",
    "Contemporâneo I" => "Contemp. I",
    "Contemporâneo Adultos" => "Contemp. Adultos",
    "Flexibilidade e Alongamentos" => "Flexibilidade e Along.",
    "Contemporâneo Kids" => "Contemp. Kids",
];

// 3. Age calculation
function calculateAge($birthdate)
{
    $birth = new DateTime($birthdate);
    $today = new DateTime();
    $age = $birth->diff($today);
    return $age->y;
}
$idade_aluno = calculateAge($student_data["data_nascimento"]);

// 4. Get attendance data
$total_presente = $total_ausente = 0;
$modalidades = [];
$result = $emotionModel->presencasAluno($id_alunos);

while ($row = $result->fetch_array()) {
    extract($row);

    if (!isset($modalidades[$modalidade])) {
        $modalidades[$modalidade] = [
            "presente" => 0,
            "ausente" => 0,
            "total" => 0,
            // Use hard-coded abbreviations instead of database lookup
            "abreviatura" => isset($modalidades_abbreviations[$modalidade])
                ? $modalidades_abbreviations[$modalidade]
                : $modalidade,
        ];
    }

    // COMMENTED OUT: Old database-dependent abbreviation logic
    // Always update abbreviation in case we get it from a later row
    // if (!empty($abreviatura)) {
    //     $modalidades[$modalidade]["abreviatura"] = $abreviatura;
    // }

    if ($presente == 1) {
        $total_presente++;
        $modalidades[$modalidade]["presente"]++;
    } else {
        $total_ausente++;
        $modalidades[$modalidade]["ausente"]++;
    }

    $modalidades[$modalidade]["total"]++;
}

// 5. Aulas presentes/ausentes
$aulas_presentes = $total_presente;
$aulas_ausentes = $total_ausente;
$total_aulas = $total_presente + $total_ausente;

// 6. Assiduidade percentage
$assiduidade_percentage =
    $total_aulas > 0 ? round(($total_presente / $total_aulas) * 100, 0) : 0;

// 7. Calculate percentages for each modalidade (for progress bars)
$modalidades_percentages = [];
foreach ($modalidades as $nome => $data) {
    $modalidades_percentages[$nome] =
        $data["total"] > 0
            ? round(($data["presente"] / $data["total"]) * 100, 0)
            : 0;
}

// 8. Total modalidades and hours for X replacements
$total_modalidades = count($modalidades);
$total_horas = ceil(($total_presente * 55) / 60); // Assuming 55 minutes per class

// 9. Get eventos with destaque ativo (highlighted events)
$eventos_destaque = [];
$query_eventos = "
    SELECT e.nome, e.data
    FROM eventos e
    INNER JOIN alunos_eventos ae ON ae.id_eventos = e.id
    WHERE ae.id_alunos = {$id_alunos} AND ae.presente = 1 AND ae.destaque = 1
    ORDER BY e.data DESC
";
$result_eventos = $emotionModel->mysqli->query($query_eventos);
$num_eventos_destaque = 0;
if ($result_eventos && $result_eventos->num_rows > 0) {
    while ($row = $result_eventos->fetch_array()) {
        $eventos_destaque[] = [
            "nome" => $row["nome"],
            "data" => $row["data"],
        ];
        $num_eventos_destaque++;
    }
}

// Check if certificate image exists (try JPEG first, then PNG)
$backgroundPath = __DIR__ . "/img/{$certificado}.jpg";
if (!file_exists($backgroundPath)) {
    $backgroundPath = __DIR__ . "/img/{$certificado}.png";
    if (!file_exists($backgroundPath)) {
        die("Error: Certificate image not found");
    }
}

// DEBUG: Show modalidades data before generating PDF
if (isset($_GET["debug"])) {
    echo "<h1>DEBUG: Modalidades Data</h1>";
    echo "<pre>";
    print_r($modalidades);
    echo "</pre>";

    echo "<h2>Testing Display Logic</h2>";
    foreach ($modalidades_percentages as $nome_modalidade => $percentage) {
        $modalidade_data = $modalidades[$nome_modalidade];
        $abreviatura = $modalidade_data["abreviatura"] ?? "NOT SET";

        echo "<p><strong>$nome_modalidade</strong><br>";
        echo "Abbreviation: '$abreviatura'<br>";
        echo "Will display: ";
        if (
            !empty($abreviatura) &&
            trim($abreviatura) != "" &&
            $abreviatura != $nome_modalidade
        ) {
            echo "<strong>$abreviatura</strong> (using abbreviation)";
        } else {
            echo "<strong>$nome_modalidade</strong> (using full name)";
        }
        echo "</p>";
    }
    exit();
}

// Generate PDF if requested, otherwise show data
if (isset($_GET["generate"])) {
    try {
        // Create PDF instance with custom class for rounded rectangles
        class CertificatePDF extends FPDF
        {
            function RoundedRect($x, $y, $w, $h, $r, $style = "")
            {
                $k = $this->k;
                $hp = $this->h;
                if ($style == "F") {
                    $op = "f";
                } elseif ($style == "FD" || $style == "DF") {
                    $op = "B";
                } else {
                    $op = "S";
                }
                $MyArc = (4 / 3) * (sqrt(2) - 1);
                $this->_out(
                    sprintf("%.2F %.2F m", ($x + $r) * $k, ($hp - $y) * $k),
                );
                $xc = $x + $w - $r;
                $yc = $y + $r;
                $this->_out(sprintf("%.2F %.2F l", $xc * $k, ($hp - $y) * $k));

                $this->_Arc(
                    $xc + $r * $MyArc,
                    $yc - $r,
                    $xc + $r,
                    $yc - $r * $MyArc,
                    $xc + $r,
                    $yc,
                );
                $xc = $x + $w - $r;
                $yc = $y + $h - $r;
                $this->_out(
                    sprintf("%.2F %.2F l", ($x + $w) * $k, ($hp - $yc) * $k),
                );
                $this->_Arc(
                    $xc + $r,
                    $yc + $r * $MyArc,
                    $xc + $r * $MyArc,
                    $yc + $r,
                    $xc,
                    $yc + $r,
                );
                $xc = $x + $r;
                $yc = $y + $h - $r;
                $this->_out(
                    sprintf("%.2F %.2F l", $xc * $k, ($hp - ($y + $h)) * $k),
                );
                $this->_Arc(
                    $xc - $r * $MyArc,
                    $yc + $r,
                    $xc - $r,
                    $yc + $r * $MyArc,
                    $xc - $r,
                    $yc,
                );
                $xc = $x + $r;
                $yc = $y + $r;
                $this->_out(sprintf("%.2F %.2F l", $x * $k, ($hp - $yc) * $k));
                $this->_Arc(
                    $xc - $r,
                    $yc - $r * $MyArc,
                    $xc - $r * $MyArc,
                    $yc - $r,
                    $xc,
                    $yc - $r,
                );
                $this->_out($op);
            }

            function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
            {
                $h = $this->h;
                $this->_out(
                    sprintf(
                        "%.2F %.2F %.2F %.2F %.2F %.2F c ",
                        $x1 * $this->k,
                        ($h - $y1) * $this->k,
                        $x2 * $this->k,
                        ($h - $y2) * $this->k,
                        $x3 * $this->k,
                        ($h - $y3) * $this->k,
                    ),
                );
            }
        }

        $pdf = new CertificatePDF("P", "mm", "A4"); // Portrait orientation
        $pdf->AddPage();

        // Add background image covering the entire page
        // A4 portrait dimensions: 210mm x 297mm
        $pdf->Image($backgroundPath, 0, 0, 210, 297);

        // Add Source Sans Variable Roman font
        $path = __DIR__ . "/font/";
        $pdf->AddFont($fontFamily, "", $fontFamily . ".php");

        // Add text overlays on the certificate
        $pdf->SetFont($fontFamily, "", 12);

        // Student name (large text, left side - positioned exactly like Sara example)
        $pdf->SetFont($fontFamily, "", 48);
        $pdf->SetTextColor(0, 0, 0);

        $x_base = 39;
        // First name
        $nome_parts = explode(" ", $nome_aluno);
        $first_name = $nome_parts[0];
        $pdf->SetXY($x_base, 52);
        $pdf->Cell(60, 15, utf8_decode($first_name), 0, 0, "L");

        // Middle name if exists
        if (count($nome_parts) > 2) {
            $middle_name = $nome_parts[1];
            $pdf->SetFont($fontFamily, "", 32);
            $pdf->SetXY($x_base, 68);
            $pdf->Cell(60, 15, utf8_decode($middle_name), 0, 0, "L");
        }

        // Last name
        if (count($nome_parts) > 1) {
            $last_name = end($nome_parts);
            $y_pos = count($nome_parts) > 2 ? 83 : 68;
            $pdf->SetFont($fontFamily, "", 32);
            $pdf->SetXY($x_base, $y_pos);
            $pdf->Cell(60, 15, utf8_decode($last_name), 0, 0, "L");
        }

        // Student photo (positioned like reference - top right, aligned with ausente line)
        $photo_path = __DIR__ . "/alunos/{$id_alunos}.jpg";
        if (file_exists($photo_path)) {
            // Photo dimensions and position - reasonable width, bottom aligned
            $photo_width = 50;
            $photo_height = 75;
            $photo_x = 125;
            $photo_y = 50;

            $pdf->Image(
                $photo_path,
                $photo_x,
                $photo_y,
                $photo_width,
                $photo_height,
            );
        }

        // Attendance numbers (left side - positioned closer to name)
        $pdf->SetFont($fontFamily, "", 11);
        $pdf->SetXY($x_base, 115);
        $pdf->Cell(33, 5, "PRESENTE", 0, 0, "L");
        $pdf->SetXY(68, 115);
        $pdf->Cell(20, 5, $aulas_presentes, 0, 0, "L");

        $pdf->SetXY($x_base, 121);
        $pdf->Cell(33, 5, "AUSENTE", 0, 0, "L");
        $pdf->SetXY(68, 121);
        $pdf->Cell(20, 5, $aulas_ausentes, 0, 0, "L");

        // Age number positioned horizontally next to ausente numbers (spanning from presente top to ausente bottom)
        $pdf->SetFont($fontFamily, "", 37);
        // Position age differently based on single or double digits
        if ($idade_aluno < 10) {
            // Single digit age - position more to the right
            $age_x = 100;
            $anos_x = 107;
        } else {
            // Double digit age - keep current position
            $age_x = 93;
            $anos_x = 107;
        }

        $pdf->SetXY($age_x, 113);
        $pdf->Cell(15, 15, $idade_aluno, 0, 0, "L");

        // "anos" text positioned next to age number
        $pdf->SetFont($fontFamily, "", 11);
        $pdf->SetXY($anos_x, 121);
        $pdf->Cell(20, 5, "anos", 0, 0, "L");

        // Assiduidade percentage (positioned like reference)
        $pdf->SetFont($fontFamily, "", 11);
        $pdf->SetXY($x_base, 135);
        $pdf->Cell(
            100,
            6,
            utf8_decode("ASSIDUIDADE     {$assiduidade_percentage}%"),
            0,
            0,
            "L",
        );

        // Certificate text with dynamic data (positioned exactly like reference)
        $pdf->SetFont($fontFamily, "", 16);
        $pdf->SetXY($x_base, 163);
        // Use alcunha if available, otherwise check if first name is shared with others
        if ($alcunha_aluno) {
            $nome_display = $alcunha_aluno;
        } else {
            $nome_parts_for_display = explode(" ", $nome_aluno);
            $first_name = $nome_parts_for_display[0];

            // Check if another person shares the same first name
            $duplicate_check = $emotionModel->mysqli->query("
                SELECT COUNT(*) as count
                FROM alunos
                WHERE nome LIKE '{$first_name} %' AND id != {$id_alunos}
            ");
            $duplicate_result = $duplicate_check->fetch_array();
            $has_duplicate_first_name = $duplicate_result["count"] > 0;

            if (
                $has_duplicate_first_name &&
                count($nome_parts_for_display) >= 3
            ) {
                // Has duplicate first name and has middle name: use first + middle
                $nome_display =
                    $nome_parts_for_display[0] .
                    " " .
                    $nome_parts_for_display[1];
            } else {
                // No duplicate first name OR no middle name: use just first
                $nome_display = $nome_parts_for_display[0];
            }
        }
        $last_name = count($nome_parts) > 1 ? " " . end($nome_parts) : "";
        $cert_text = "A Emotion Dance Academy certifica que {$nome_display}";
        $pdf->Cell(160, 6, utf8_decode($cert_text), 0, 0, "L");

        $pdf->SetXY($x_base, 170);
        $pdf->Cell(
            160,
            6,
            utf8_decode("concluiu com sucesso o Ano Letivo {$ano_letivo}."),
            0,
            0,
            "L",
        );

        // Dynamic text with X replacements (positioned like reference)
        $pdf->SetFont($fontFamily, "", 11);
        $pdf->SetXY($x_base, 183);
        $modalidades_word =
            $total_modalidades == 1 ? "modalidade" : "modalidades";
        $dynamic_text = "Frequentaste {$total_modalidades} {$modalidades_word} e estiveste presente em {$aulas_presentes} aulas de {$total_aulas}, o que dá";
        $pdf->Cell(160, 5, utf8_decode($dynamic_text), 0, 0, "L");

        $pdf->SetXY($x_base, 188);
        $dynamic_text2 = utf8_decode(
            "um total aproximado de {$total_horas} horas dedicadas a evoluir o teu movimento.",
        );
        $pdf->Cell(160, 5, $dynamic_text2, 0, 0, "L");

        // Events section with improved formatting (only if student attended featured events)
        if ($num_eventos_destaque > 0) {
            $pdf->SetXY($x_base, 198);
            // $eventos_intro = "Estiveste ainda presente em {$num_eventos_destaque} eventos, com destaque para:";
            $eventos_intro =
                "Estiveste ainda presente em eventos, com destaque para:";
            $pdf->Cell(160, 6, utf8_decode($eventos_intro), 0, 0, "L");

            $y_pos = 203;
            foreach ($eventos_destaque as $evento) {
                $pdf->SetXY($x_base, $y_pos + 2);
                $pdf->Cell(5, 5, chr(149), 0, 0, "L");
                $pdf->SetXY(43, $y_pos + 2);
                $evento_text =
                    $evento["nome"] .
                    " (" .
                    date("d/m/Y", strtotime($evento["data"])) .
                    ")";
                $pdf->Cell(140, 5, utf8_decode($evento_text), 0, 0, "L");
                $y_pos += 6;
            }
            $final_y = $y_pos + 3;
        } else {
            $final_y = 198;
        }

        // Final gratitude message
        $pdf->SetXY($x_base, $final_y);
        $pdf->Cell(
            160,
            6,
            utf8_decode("Agradecemos pelo empenho, dedicação e compromisso."),
            0,
            0,
            "L",
        );

        $pdf->SetXY($x_base, $final_y + 5);
        $pdf->Cell(
            160,
            6,
            utf8_decode(
                "Que possamos continuar a fazer parte do teu crescimento.",
            ),
            0,
            0,
            "L",
        );

        $pdf->SetXY($x_base, $final_y + 16);
        $pdf->Cell(
            160,
            6,
            utf8_decode(
                "Obrigada por fazeres parte desta família bonita e contamos contigo",
            ),
            0,
            0,
            "L",
        );

        $pdf->SetXY($x_base, $final_y + 21);
        $pdf->Cell(
            160,
            6,
            utf8_decode("para o próximo ano letivo!"),
            0,
            0,
            "L",
        );

        // Progress bars for modalidades (positioned exactly like reference - 6 horizontal bars)
        $bar_y = 147;
        $bar_width = 20;
        $bar_height = 2.2;
        $bar_spacing = 23;
        $x_base;

        // Colors for different modalidades (repeating 3-color pattern twice)
        $modalidade_colors = [
            [254, 239, 220], // Beige #feefdc
            [44, 37, 75], // Dark blue #2c254b
            [169, 43, 77], // Dark red #a92b4d
            [254, 239, 220], // Beige #feefdc (repeat)
            [44, 37, 75], // Dark blue #2c254b (repeat)
            [169, 43, 77], // Dark red #a92b4d (repeat)
        ];

        $modalidade_index = 0;
        foreach ($modalidades_percentages as $nome_modalidade => $percentage) {
            if ($modalidade_index >= 6) {
                break;
            } // Only show 6 bars

            $x = $x_base + $modalidade_index * $bar_spacing;

            // Modalidade name (above bar) - use abbreviation if available
            $pdf->SetFont($fontFamily, "", 7);
            $pdf->SetXY($x, $bar_y - 4);

            // SIMPLE IF STATEMENT FOR ABBREVIATIONS
            if (isset($modalidades_abbreviations[$nome_modalidade])) {
                $display_name = $modalidades_abbreviations[$nome_modalidade];
            } else {
                $display_name = $nome_modalidade;
            }

            // COMMENTED OUT: Old database-dependent abbreviation logic
            // Get modalidade data and use abbreviation if it exists
            // $modalidade_data = $modalidades[$nome_modalidade];
            // $abreviatura = $modalidade_data["abreviatura"];
            //
            // Use abbreviation if it exists and is different from full name, otherwise use full name
            // if (
            //     !empty($abreviatura) &&
            //     trim($abreviatura) != "" &&
            //     $abreviatura != $nome_modalidade
            // ) {
            //     $display_name = trim($abreviatura);
            // } else {
            //     $display_name = $nome_modalidade;
            // }

            $pdf->Cell($bar_width, 3, utf8_decode($display_name), 0, 0, "L");

            // Progress bar background (light gray) - rounded
            $pdf->SetFillColor(240, 240, 240);
            $pdf->RoundedRect($x, $bar_y, $bar_width, $bar_height, 1, "F");

            // Progress bar fill (cycling through colors)
            $color_index = $modalidade_index % count($modalidade_colors);
            $color = $modalidade_colors[$color_index];
            $pdf->SetFillColor($color[0], $color[1], $color[2]);

            $fill_width = ($bar_width * $percentage) / 100;
            if ($fill_width > 0) {
                $pdf->RoundedRect($x, $bar_y, $fill_width, $bar_height, 1, "F");
            }

            // Progress bar border - rounded
            $pdf->SetDrawColor(180, 180, 180);
            $pdf->SetLineWidth(0.2);
            $pdf->RoundedRect($x, $bar_y, $bar_width, $bar_height, 1, "D");

            // Percentage text (below bar)
            $pdf->SetFont($fontFamily, "", 6);
            $pdf->SetXY($x - 6, $bar_y + $bar_height + 1);
            $pdf->Cell($bar_width, 3, $percentage . "%", 0, 0, "C");

            $modalidade_index++;
        }

        // Generate filename
        $clean_name = preg_replace(
            "/[^a-zA-Z0-9_\-]/",
            "_",
            $student_data["nome"],
        );
        $filename = "certificado_" . $clean_name . "_" . date("Y-m-d") . ".pdf";

        // Output PDF
        $pdf->Output("I", $filename);
    } catch (Exception $e) {
        die("Error generating PDF: " . $e->getMessage());
    }
} else {
    // === DISPLAY VARIABLES FOR TESTING ===
    echo "<h2>Certificate Data for Student ID: {$id_alunos}</h2>";
    echo "<strong>Ano Letivo:</strong> {$ano_letivo}<br>";
    echo "<strong>Nome:</strong> {$nome_aluno}<br>";
    echo "<strong>Alcunha:</strong> {$alcunha_aluno}<br>";
    echo "<strong>Idade:</strong> {$idade_aluno} anos<br>";
    echo "<strong>Aulas Presentes:</strong> {$aulas_presentes}<br>";
    echo "<strong>Aulas Ausentes:</strong> {$aulas_ausentes}<br>";
    echo "<strong>Assiduidade:</strong> {$assiduidade_percentage}%<br>";
    echo "<strong>Total Modalidades:</strong> {$total_modalidades}<br>";
    echo "<strong>Total Horas:</strong> {$total_horas}<br>";

    echo "<h3>Modalidades and Progress:</h3>";
    foreach ($modalidades_percentages as $nome => $percentage) {
        echo "- {$nome}: {$percentage}%<br>";
    }

    echo "<h3>Eventos Destaque:</h3>";
    if (count($eventos_destaque) > 0) {
        foreach ($eventos_destaque as $evento) {
            echo "- {$evento["nome"]} ({$evento["data"]})<br>";
        }
    } else {
        echo "No events found<br>";
    }

    echo "<hr>";
    echo "<p><strong>Variables ready for PDF overlay!</strong></p>";
    echo "<p><a href='pdf.php?id_alunos={$id_alunos}&generate=1'>Generate PDF with background only</a></p>";
}

?>
