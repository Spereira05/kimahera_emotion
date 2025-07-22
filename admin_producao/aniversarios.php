<?php

if($_GET['hash'] != 'a5a0b172f750547eaf337b3070c33ff7de3b8173'){exit;}

require("config.php");

$result = $mysqli->query
("
    SELECT `id`, `nome`, `data_nascimento`, `ativo`, `data_inscricao`, `telemovel`, `telemovel_ee`, `nome_ee` FROM `alunos` WHERE `ativo` = 1
    UNION ALL
    SELECT `id`, `nome`, `data_nascimento`, `ativo`, 0, `telemovel`, 0, 0 FROM `professores` WHERE `ativo` = 1
");
$events = '';
while($row = $result->fetch_array())
{
    extract($row);

    #Modalidades
    $modalidades = '';
    $result_aulas = $mysqli->query
    ("
        SELECT `alunos_aulas`.*, `modalidades`.`nome` AS `modalidade`
        FROM `alunos_aulas`
        LEFT JOIN `aulas` ON `aulas`.`id` = `alunos_aulas`.`id_aulas`
        LEFT JOIN `modalidades` ON `modalidades`.`id` = `aulas`.`id_modalidades`
        WHERE `alunos_aulas`.`id_alunos` = {$id}
        GROUP BY `modalidades`.id
    ");
    while($aula = $result_aulas->fetch_array())
    {
        if($modalidades){$modalidades .= ', ';}
        $modalidades .= "{$aula['modalidade']}";
    }
    #/Modalidades

    $hoje = date('Y-m-d');
    $mes_dia = date('m-d', strtotime($data_nascimento));
    $mes_dia_atual = date('m-d', strtotime($hoje));
    $ano_atual = date('Y');
    $ano = $ano_atual = date('Y');
    $idade = $ano_atual - date('Y', strtotime($data_nascimento));
    //if($mes_dia < $mes_dia_atual){$ano = $ano_atual + 1;}

    $aniv = "{$ano}-{$mes_dia}";
	$data = date('r', strtotime($aniv));
	$cdata = strtotime($data);
	$dtstart = date('Ymd', $cdata);
    $dtend = date('Ymd', $cdata.' +1 day');

    $descricao = '';
	if($telemovel != $telemovel_ee){$descricao .= "{$telemovel} ({$nome})";};
    if($telemovel_ee)
    {
        if($descricao){$descricao .= "<br/>";}
        $descricao .= "{$telemovel_ee} ({$nome_ee})";
    }
	
	if($alcunha){$nome = $alcunha;}
    else
    {
        $nome_apelido = explode(' ', $nome);
        $nome = $nome_apelido[0].' '.end($nome_apelido);
    }
	
    if(!$data_inscricao){$nome = strtoupper($nome);} #Prof /#
    else{$descricao .= "<br/>{$modalidades}";}

	$events .= "BEGIN:VEVENT\n";
	$events .= "DTSTART;VALUE=DATE:{$dtstart}\n";
	$events .= "DTEND;VALUE=DATE:{$dtend}\n";
    //$events .= "RRULE:FREQ=YEARLY\n";
	$events .= "SUMMARY:🎂 {$nome} ({$idade})\n";
    
	$events .= "DESCRIPTION:{$descricao}\n";
	$events .= "END:VEVENT\n";
}

header("Content-type:text/plain; charset=UTF-8");
?>
BEGIN:VCALENDAR
PRODID:-//Emotion//Emotion//PT
VERSION:2.0
CALSCALE:GREGORIAN
METHOD:PUBLISH
X-WR-CALNAME:Aniversários Emotion
X-WR-TIMEZONE:Europe/Lisbon
X-WR-CALDESC:Aniversários Alunos e Professores Emotion
BEGIN:VTIMEZONE
TZID:Europe/Lisbon
X-LIC-LOCATION:Europe/Lisbon
BEGIN:STANDARD
TZOFFSETFROM:+0100
TZOFFSETTO:+0000
TZNAME:WET
DTSTART:20220101T010000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:+0000
TZOFFSETTO:+0100
TZNAME:WEST
DTSTART:20220101T010000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
END:VTIMEZONE
<?php echo $events; ?>
END:VCALENDAR