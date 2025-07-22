<?php
require('config.php');
require('model.php');
require('controller.php');
require_once __DIR__ . '/vendor/autoload.php';
require_once('fpdf/fpdf.php');

define('EURO', chr(128));

class PDF extends FPDF {
    private $mes_referencia;
    private $professor;
    private $foto_professor;
    private $total_aulas = 0;
    private $total_horas = 0;
    private $total_valor = 0;
    private $total_assiduidade = 0;

    function __construct() {
        parent::__construct('P', 'mm', 'A4');
        $this->SetAutoPageBreak(true, 20);
        $this->SetMargins(27, 15, 27);
        $this->total_assiduidade = 0;
    }

    function Header() {
        $this->SetFillColor(166, 42, 76);
        $x_bloco = 27;
        $y_bloco = 15;
        $w_bloco = 156;
        $margem = 2;
        $tamanho = 22; // tamanho do texto e do logo (em mm)
        $h_bloco = $tamanho + 2 * $margem;

        // Desenha o bloco vermelho
        $this->SetXY($x_bloco, $y_bloco);
        $this->Cell($w_bloco, $h_bloco, '', 0, 0, 'C', true);

        // Centraliza o texto vertical e horizontalmente dentro do bloco
        $pt = 0.35 * 72 * $tamanho / 25.4; // conversão mm para pt
        $this->SetFont('Arial','B', $pt);
        $this->SetTextColor(253, 236, 226);
        $margem_esquerda = 2;
        $this->SetXY($x_bloco + $margem_esquerda + 8, $y_bloco + $margem);
        $this->Cell($w_bloco - $margem - $margem, $tamanho, utf8_decode('CONTABILIZAÇÃO DE HORAS'), 0, 0, 'L', false);

        // Logo com o mesmo tamanho do texto, centralizado verticalmente
        $logo_largura = $tamanho + 2; // aumenta o logo em 2mm em relação ao texto
        $logo_x = $x_bloco + $w_bloco - $margem - $logo_largura - 2.5;
        // Centralizar verticalmente o logo em relação ao título
        $logo_y = $y_bloco + $margem + (($tamanho - $logo_largura) / 2) - 0.6;
        if (file_exists('img/logo.png')) {
            $this->Image('img/logo.png', $logo_x, $logo_y, $logo_largura, $logo_largura);
        } elseif (file_exists('img/logo.jpg')) {
            $this->Image('img/logo.jpg', $logo_x, $logo_y, $logo_largura, $logo_largura);
        }
        $this->Ln($h_bloco);
        $this->SetTextColor(40, 40, 40);
        // Foto e dados do professor
        $tem_foto = false;
        if ($this->foto_professor && file_exists($this->foto_professor)) {
            $tem_foto = true;
        }
        if ($tem_foto && $this->foto_professor) {
            // Layout COM FOTO
            // Foto do professor abaixo da célula do título (lado esquerdo)
            $foto_y = 46;
            $foto_altura = 30; // altura proporcional
            $margem = 20; // metade da margem anterior
            $this->Image($this->foto_professor, 27, $foto_y, $foto_altura); // Foto 25mm de largura, altura proporcional

            // Nome do professor em grande ao lado da foto
            $this->SetFont('Arial','B',18);
            $this->SetXY(65, $foto_y + 8);
            $this->Cell(0, 8, utf8_decode($this->professor), 0, 1, 'L');

            // Mês abaixo do nome
            $this->SetFont('Arial','B',14);
            $this->SetXY(65, $foto_y + 18);
            $this->Cell(0, 6, strtolower($this->mes_referencia), 0, 1, 'L');

            // Período das aulas abaixo do mês
            $this->SetFont('Arial','',12);
            $this->SetXY(65, $foto_y + 26);
            global $periodo_pt;
            $this->Cell(0, 6, utf8_decode($periodo_pt), 0, 1, 'L');
            // Espaço extra antes da tabela
            $y_fim_foto = $foto_y + $foto_altura;
            $this->SetY($y_fim_foto + $margem);
        } else {
            $this->SetFont('Arial','B',16);
            $this->Ln(18);
            $this->Cell(0, 8, utf8_decode($this->professor ? $this->professor : 'Professor'), 0, 1, 'C');
            $this->SetFont('Arial','B',12);
            $this->Cell(0, 6, strtolower($this->mes_referencia ? $this->mes_referencia : ''), 0, 1, 'C');
            $this->SetFont('Arial','',12);
            global $periodo_pt;
            $this->Cell(0, 6, utf8_decode($periodo_pt), 0, 1, 'C');
            $this->Ln(15);
        }
        // Ao final do header, garantir o cabeçalho da tabela em todas as páginas
        $this->TableHeader();
    }

    function Footer() {
        $this->SetY(-22);
        $this->SetFont('Arial','I',8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0,10,utf8_decode('Documento gerado em ').date('d/m/Y H:i'),0,0,'L');
        $this->Cell(0,10,utf8_decode('Página ').$this->PageNo().'/{nb}',0,0,'R');
    }

    function SetMesReferencia($mes) {
        $this->mes_referencia = $mes;
    }
    function SetProfessor($professor) {
        $this->professor = $professor;
    }
    function SetFotoProfessor($foto_path) {
        $this->foto_professor = $foto_path;
    }
    function TableHeader() {
        $this->SetFont('Arial','B',10);
        $this->SetFillColor(166, 42, 76);
        $this->SetTextColor(255);
        $this->SetDrawColor(180,180,180); // cor única para as bordas
        $this->Cell(30,8,'Data',1,0,'C', true);
        $this->Cell(40,8,'Modalidade',1,0,'C', true);
        $this->Cell(25,8,utf8_decode('Horário'),1,0,'C', true);
        $this->Cell(20,8,utf8_decode('Duração'),1,0,'C', true);
        $this->Cell(20,8,'Valor',1,0,'C', true);
        $this->Cell(21,8,'Assid.',1,1,'C', true);
        $this->SetFillColor(255);
        $this->SetTextColor(0);
    }
    function AddAula($data, $dia_semana, $modalidade, $horario, $duracao, $valor, $assiduidade) {
        static $fill = false;
        $this->SetFont('Arial','',9);
        $this->SetFillColor(245, 245, 245);
        $this->SetDrawColor(180,180,180); // cor única para as bordas
        $data_formatada = $data.' ('.utf8_decode($dia_semana).')';
        $this->Cell(30,8,$data_formatada,1,0,'C', $fill);
        $this->Cell(40,8,utf8_decode($modalidade),1,0,'L', $fill);
        $this->Cell(25,8,$horario,1,0,'C', $fill);
        $this->Cell(20,8,str_replace('.',',',$duracao).'h',1,0,'R', $fill);
        $this->Cell(20,8,number_format($valor,2,',','.').EURO,1,0,'R', $fill);
        // Barra de assiduidade centralizada
        $cellWidth = 21;
        $cellHeight = 8;
        $barWidth = 15; // largura máxima da barra
        $barHeight = 5; // altura da barra
        $percent = max(0, min(100, intval($assiduidade)));
        // Salvar posição inicial da célula
        $x = $this->GetX();
        $y = $this->GetY();
        // Desenhar célula de assiduidade (borda e fundo)
        $this->Cell($cellWidth, $cellHeight, '', 1, 0, 'C', $fill);
        // Calcular posição da barra centralizada
        $barX = $x + ($cellWidth - $barWidth) / 2;
        $barY = $y + ($cellHeight - $barHeight) / 2;
        // Cor da barra
        if ($percent >= 70) {
            $this->SetFillColor(40, 167, 69); // verde
        } elseif ($percent >= 50) {
            $this->SetFillColor(255, 193, 7); // amarelo
        } else {
            $this->SetFillColor(220, 53, 69); // vermelho
        }
        // Barra preenchida
        $this->Rect($barX, $barY, ($barWidth * $percent/100), $barHeight, 'F');
        // Contorno da barra
        $this->SetDrawColor(180,180,180);
        $this->Rect($barX, $barY, $barWidth, $barHeight, 'D');
        // Texto da percentagem centralizado na barra
        $this->SetFont('Arial','B',7);
        $this->SetTextColor(0);
        $this->SetXY($barX, $barY);
        $this->Cell($barWidth, $barHeight, $percent.'%', 0, 0, 'C');
        // Voltar o cursor para o fim da linha
        $this->SetXY($x + $cellWidth, $y);
        $this->Ln($cellHeight);
        $fill = !$fill;
        $this->total_aulas++;
        $this->total_horas += floatval(str_replace(',','.',$duracao));
        $this->total_valor += $valor;
        $this->total_assiduidade += $assiduidade;
    }
    function AddTotal() {
        $this->SetFont('Arial','B',9);
        $this->SetFillColor(253, 236, 226);
        $this->SetTextColor(0, 0, 0);
        $this->SetDrawColor(180,180,180); // cor única para as bordas
        $this->Cell(30,8,utf8_decode('AULAS'),'LTB',0,'L',true);
        $this->Cell(40,8,$this->total_aulas,'TB',0,'R',true);
        $this->Cell(25,8,'TOTAL','TB',0,'R',true);
        $total_horas_formatado = ($this->total_horas == intval($this->total_horas)) ? intval($this->total_horas).'h' : str_replace('.', ',', $this->total_horas).'h';
        $this->Cell(20,8,$total_horas_formatado,'TB',0,'R',true);
        $this->Cell(20,8,number_format($this->total_valor,2,',','.').EURO,'TB',0,'R',true);
        // Calcular média da assiduidade usando o controller
        global $emotionController, $aulas;
        $media_assiduidade = $emotionController->mediaAssiduidadeAulas($aulas);
        $this->Cell(21,8,$media_assiduidade.'%','TBR',1,'C',true);
        $this->SetFillColor(255);
        $this->SetTextColor(0);
    }
}

$emotionModel = new EmotionModel();
$emotionController = new EmotionController();

$id_professores = isset($_GET['id_professores']) ? intval($_GET['id_professores']) : 0;
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

if (!$id_professores || !$data_inicio || !$data_fim) {
    die('Parâmetros inválidos.');
}

// Buscar dados do professor
$prof_row = $emotionModel->professor($id_professores)->fetch_array();
$nome_professor = $prof_row['nome'];

// Buscar aulas do professor no período
$result = $emotionModel->aulasProfessores(0, $id_professores, 0, $data_inicio, $data_fim);
$aulas = [];
$total_horas = 0;
$total_valor = 0;
$total_aulas = 0;

while ($row = $result->fetch_array()) {
    $data = $row['data'];
    $modalidade = $row['modalidade'];
    $horario = $row['horario'];
    $valor = floatval($row['valor']);
    $inicio_fim = explode(' > ', $horario);
    $inicio = isset($inicio_fim[0]) ? $inicio_fim[0] : '';
    $fim = isset($inicio_fim[1]) ? $inicio_fim[1] : '';
    // Calcular duração em minutos
    $minutos = 0;
    if ($inicio && $fim) {
        $t1 = strtotime($inicio);
        $t2 = strtotime($fim);
        if ($t2 > $t1) {
            $minutos = ($t2 - $t1) / 60;
        }
    }
    // Arredondar duração para cima: 55min=1h, 85min=1,5h, etc
    $duracao = 0;
    if ($minutos > 0) {
        if ($minutos <= 60) {
            $duracao = 1;
        } else {
            $duracao = ceil($minutos / 30) / 2; // 90min -> 1.5, 120min -> 2
        }
    }
    $valor_aula = $valor;
    $total_horas += $duracao;
    $total_valor += $valor_aula;
    $total_aulas++;
    // Assiduidade
    $presentes = intval($row['presentes']);
    $ausentes = intval($row['ausentes']);
    $assiduidade = ($presentes + $ausentes) > 0 ? round($presentes * 100 / ($presentes + $ausentes)) : 0;
    $aulas[] = [
        'data' => $data,
        'modalidade' => $modalidade,
        'horario' => $horario,
        'duracao' => $duracao,
        'valor' => $valor_aula,
        'assiduidade' => $assiduidade,
    ];
}

// Cabeçalho
// Substituir montagem do mês e período para português

$meses_pt = [
    '01' => 'janeiro', '02' => 'fevereiro', '03' => 'março',
    '04' => 'abril', '05' => 'maio', '06' => 'junho',
    '07' => 'julho', '08' => 'agosto', '09' => 'setembro',
    '10' => 'outubro', '11' => 'novembro', '12' => 'dezembro'
];
$ano_inicio = date('Y', strtotime($data_inicio));
$mes_inicio = date('m', strtotime($data_inicio));
$ano_fim = date('Y', strtotime($data_fim));
$mes_fim = date('m', strtotime($data_fim));
$mes_nome_inicio = $meses_pt[$mes_inicio];
$mes_nome_fim = $meses_pt[$mes_fim];
$data_inicio_pt = date('d-m-Y', strtotime($data_inicio));
$data_fim_pt = date('d-m-Y', strtotime($data_fim));

if ($ano_inicio === $ano_fim && $mes_inicio === $mes_fim) {
    // Período dentro do mesmo mês
    $mes_ano_pt = "$mes_nome_inicio $ano_inicio";
    $primeiro_dia = "01-$mes_inicio-$ano_inicio";
    $ultimo_dia = date('t', strtotime($data_inicio)) . "-$mes_inicio-$ano_inicio";
    $periodo_pt = "Aulas de $primeiro_dia a $ultimo_dia";
} else {
    // Período abrange mais de um mês
    if ($ano_inicio === $ano_fim) {
        $mes_ano_pt = "$mes_nome_inicio $ano_inicio - $mes_nome_fim $ano_fim";
    } else {
        $mes_ano_pt = "$mes_nome_inicio $ano_inicio - $mes_nome_fim $ano_fim";
    }
    $periodo_pt = "Aulas de $data_inicio_pt a $data_fim_pt";
}

// Buscar foto do professor (ajuste o caminho conforme sua estrutura)
$foto_professor = null;
$possiveis_fotos = [
    "professores/{$id_professores}.jpg",
    "professores/{$id_professores}.jpeg",
    "professores/{$id_professores}.png"
];
foreach($possiveis_fotos as $foto_path) {
    if (file_exists($foto_path)) {
        $foto_professor = $foto_path;
        break;
    }
}

$pdf = new PDF();
$pdf->SetTitle('Contabilizacao de Horas');
$pdf->AliasNbPages();
$pdf->SetProfessor($nome_professor);
$pdf->SetMesReferencia($mes_ano_pt);
if ($foto_professor) {
    $pdf->SetFotoProfessor($foto_professor);
}
$pdf->AddPage();
$dias_semana_pt = [
    'Sun' => 'Dom.',
    'Mon' => '2.ª',
    'Tue' => '3.ª',
    'Wed' => '4.ª',
    'Thu' => '5.ª',
    'Fri' => '6.ª',
    'Sat' => 'Sáb.'
];
foreach ($aulas as $aula) {
    $data_show = date('d-m-Y', strtotime($aula['data']));
    $dia_en = date('D', strtotime($aula['data']));
    $dia_semana = isset($dias_semana_pt[$dia_en]) ? $dias_semana_pt[$dia_en] : $dia_en;
    $pdf->AddAula(
        $data_show,
        $dia_semana,
        $aula['modalidade'],
        $aula['horario'],
        $aula['duracao'],
        $aula['valor'],
        $aula['assiduidade']
    );
}
$pdf->AddTotal();

// Limpa o nome do professor para evitar problemas com acentos e caracteres especiais
$nome_professor_limpo = iconv('UTF-8', 'ASCII//TRANSLIT', $nome_professor);
$nome_professor_limpo = preg_replace('/[^\\w\\s-]/u', '', $nome_professor_limpo);
$nome_professor_limpo = preg_replace('/\\s+/', ' ', $nome_professor_limpo);
$nome_professor_limpo = trim($nome_professor_limpo);

$data_inicio_fmt = date('d-m-Y', strtotime($data_inicio));
$data_fim_fmt = date('d-m-Y', strtotime($data_fim));
$nome_arquivo = 'Contabilizacao - ' . $nome_professor_limpo . ' - ' . $data_inicio_fmt . ' - ' . $data_fim_fmt . '.pdf';

$pdf->Output($nome_arquivo, 'I'); 