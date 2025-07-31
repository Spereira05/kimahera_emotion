<?php

class EmotionModel
{
    private $db_host = "localhost";
    private $db_user = "root";
    private $db_password = "";
    private $db_name = "admin_emotiondanceacademy";
    private $db_charset = "utf8";
    public $mysqli;

    public function __construct()
    {
        $this->db_connect();
    }

    private function db_connect()
    {
        $this->mysqli = new mysqli(
            $this->db_host,
            $this->db_user,
            $this->db_password,
            $this->db_name,
        );
        $this->mysqli->set_charset($this->db_charset);

        // Ensure autocommit is enabled for immediate visibility of changes
        $this->mysqli->autocommit(true);

        // Set isolation level to READ COMMITTED for immediate visibility across connections
        $this->mysqli->query(
            "SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED",
        );

        return $this->mysqli;
    }

    function horarios($id_horarios)
    {
        $id_horarios = "3";
        $result = $this->mysqli->query("
            SELECT `horarios`.*
            FROM `horarios`
            WHERE `horarios`.`id` = {$id_horarios}
        ");

        return $result;
    }

    function aluno($id_alunos)
    {
        $result = $this->mysqli->query("
            SELECT `alunos`.*
            FROM `alunos`
            WHERE `alunos`.`id` = {$id_alunos}
        ");

        return $result;
    }

    function dataAssiduidade()
    {
        $data = date("Y-m-d", strtotime("-295 day", strtotime(date("Y-m-d"))));
        // if ($_GET['generate'] == 1) {$data = '2024-09-09';}

        return $data;
    }

    function alunos($order, $limit)
    {
        $data = $this->dataAssiduidade();

        if ($limit) {
            $limit = " LIMIT {$limit} ";
        }
        if ($order) {
            $order = " ORDER BY {$order} {$dir} ";
        }

        $result = $this->mysqli->query("
            SELECT `alunos`.*,
            (
                SELECT COUNT(`presencas`.`id`)
                FROM `presencas`
                RIGHT JOIN `aulas_professores` ON (`aulas_professores`.`id_aulas` = `presencas`.`id_aulas` AND `aulas_professores`.`data` = DATE_FORMAT(`presencas`.`data`, '%Y-%m-%d'))
                WHERE (`presencas`.`data` >= '{$data}' AND `presencas`.`data` < CURDATE())
                AND `presencas`.`id_alunos` = `alunos`.`id`
            ) AS `total`,
            ROUND((
                (
                    SELECT COUNT(`presencas`.`id`)
                    FROM `presencas`
                    RIGHT JOIN `aulas_professores` ON (`aulas_professores`.`id_aulas` = `presencas`.`id_aulas` AND `aulas_professores`.`data` = DATE_FORMAT(`presencas`.`data`, '%Y-%m-%d'))
                    WHERE (`presencas`.`data` >= '{$data}' AND `presencas`.`data` < CURDATE())
                    AND `presencas`.`presente` = 1
                    AND `presencas`.`id_alunos` = `alunos`.`id`
                ) * 100 /
                (
                    SELECT COUNT(`presencas`.`id`)
                    FROM `presencas`
                    RIGHT JOIN `aulas_professores` ON (`aulas_professores`.`id_aulas` = `presencas`.`id_aulas` AND `aulas_professores`.`data` = DATE_FORMAT(`presencas`.`data`, '%Y-%m-%d'))
                    WHERE (`presencas`.`data` >= '{$data}' AND `presencas`.`data` < CURDATE())
                    AND `presencas`.`id_alunos` = `alunos`.`id`
                )
            ), 0) AS `assiduidade`
            FROM `alunos`
            WHERE `alunos`.`ativo` = 1
            {$order}
            {$limit}
        ");

        return $result;
    }

    function professores()
    {
        $result = $this->mysqli->query("
            SELECT `professores`.*
            FROM `professores`
            WHERE `professores`.`ativo` = 1
            ORDER BY `professores`.`id` ASC
        ");

        return $result;
    }

    function modalidadesProfessor($id_professores, $id_horarios)
    {
        $result = $this->mysqli->query("
            SELECT `modalidades`.`id`, `modalidades`.`nome`
            FROM `aulas_professores`
            LEFT JOIN `aulas` ON `aulas`.`id` = `aulas_professores`.`id_aulas`
            LEFT JOIN `modalidades` ON `modalidades`.`id` = `aulas`.`id_modalidades`
            WHERE `aulas_professores`.`id_professores` = {$id_professores}
            AND `aulas`.`id_horarios` = {$id_horarios}
            AND `aulas`.`ativo` = 1
            GROUP BY `modalidades`.`id`
            ORDER BY `modalidades`.`nome`
        ");

        return $result;
    }

    function aulas($id_horarios, $dia, $id_professores)
    {
        $profs = "";
        if (isset($id_professores)) {
            if ($id_professores) {
                $profs = " AND `professores`.`id` = {$id_professores} ";
            }
        }

        $result = $this->mysqli->query("
            SELECT `aulas`.*, `modalidades`.`nome` AS `modalidade`, `modalidades`.`abreviatura`, `professores`.`nome` AS `professor`, `professores`.`alcunha`, `professores`.`valor`
            FROM `aulas`
            LEFT JOIN `modalidades` ON `modalidades`.`id` = `aulas`.`id_modalidades`
            LEFT JOIN `professores` ON `professores`.`id` = `aulas`.`id_professores`
            WHERE `aulas`.`id_horarios` = {$id_horarios}
            AND `aulas`.`{$dia}` = 1
            AND `aulas`.`ativo` = 1
            {$profs}
            ORDER BY `aulas`.`inicio`, `aulas`.`estudio`
        ");

        return $result;
    }

    function aulasProfessores(
        $id,
        $id_professores,
        $id_modalidades,
        $data_inicio,
        $data_fim,
    ) {
        $profs = "";
        if (isset($id_professores)) {
            if ($id_professores) {
                $profs = " AND `professores`.`id` = {$id_professores} ";
            }
        }

        $modalidades = "";
        if (isset($id_modalidades)) {
            if ($id_modalidades) {
                $profs = " AND `modalidades`.`id` = {$id_modalidades} ";
            }
        }

        $aula = "";
        if (isset($id)) {
            if ($id) {
                $aula = " AND `aulas_professores`.`id` = {$id} ";
            }
        }

        $result = $this->mysqli->query("
            SELECT `aulas`.`id` AS `id_aulas`,
            `aulas_professores`.`id`,
            `aulas_professores`.`data`,
            `aulas_professores`.`dia`,
            `aulas_professores`.`id_professores`,
            IF(`aulas`.`descricao` IS NOT NULL, CONCAT(`modalidades`.`nome`,' ',`aulas`.`descricao`), `modalidades`.`nome`) AS `modalidade`,
            /*`professores`.`nome` AS `professor`,*/
            IF(`professores`.`alcunha` IS NOT NULL, `professores`.`alcunha`, `professores`.`nome`) AS `professor`,
            `aulas_professores`.`valor`,
            CONCAT(DATE_FORMAT(`aulas_professores`.`inicio`,'%H:%i'), ' > ', DATE_FORMAT(`aulas_professores`.`fim`,'%H:%i')) AS `horario`,
            `aulas`.`estudio`,
            (
                SELECT COUNT(`presencas`.`id`)
                FROM `presencas`
                WHERE `presencas`.`id_aulas` = `aulas_professores`.`id_aulas`
                AND DATE_FORMAT(`presencas`.`data`, '%Y-%m-%d') = `aulas_professores`.`data`
                AND `presencas`.`presente` = 1
            ) AS `presentes`,
            (
                SELECT COUNT(`presencas`.`id`)
                FROM `presencas`
                WHERE `presencas`.`id_aulas` = `aulas_professores`.`id_aulas`
                AND DATE_FORMAT(`presencas`.`data`, '%Y-%m-%d') = `aulas_professores`.`data`
                AND `presencas`.`presente` = 0
            ) AS `ausentes`
            FROM `aulas_professores`
            LEFT JOIN `professores` ON `professores`.`id` = `aulas_professores`.`id_professores`
            LEFT JOIN `aulas` ON `aulas`.`id` = `aulas_professores`.`id_aulas`
            LEFT JOIN `modalidades` ON `modalidades`.`id` = `aulas`.`id_modalidades`
            WHERE (`aulas_professores`.`data` BETWEEN '{$data_inicio}' AND '{$data_fim}')
            {$aula}
            {$profs}
            {$modalidades}
            /* HAVING (`presentes` > 0 OR `ausentes` > 0) */
            ORDER BY `aulas_professores`.`data`, `aulas_professores`.`inicio`
        ");

        return $result;
    }

    function alunosTurma($id_aulas, $dia)
    {
        $data = $this->dataAssiduidade();
        if ($dia) {
            $dia = "AND `alunos_aulas`.`dia` = '{$dia}'";
        }

        $result = $this->mysqli->query("
            SELECT `alunos_aulas`.*, `alunos`.`nome`, `alunos`.`alcunha`, `alunos`.`data_inscricao`, `alunos`.`data_nascimento`, `aulas`.`id_professores`,
            ROUND((
                (
                    SELECT COUNT(`presencas`.`id`)
                    FROM `presencas`
                    RIGHT JOIN `aulas_professores` ON (`aulas_professores`.`id_aulas` = `presencas`.`id_aulas` AND `aulas_professores`.`data` = DATE_FORMAT(`presencas`.`data`, '%Y-%m-%d'))
                    WHERE (`presencas`.`data` >= '{$data}' AND `presencas`.`data` < CURDATE())
                    AND `presencas`.`presente` = 1
                    AND `presencas`.`id_alunos` = `alunos`.`id`
                    AND `presencas`.`id_aulas` = {$id_aulas}
                ) * 100 /
                (
                    SELECT COUNT(`presencas`.`id`)
                    FROM `presencas`
                    RIGHT JOIN `aulas_professores` ON (`aulas_professores`.`id_aulas` = `presencas`.`id_aulas` AND `aulas_professores`.`data` = DATE_FORMAT(`presencas`.`data`, '%Y-%m-%d'))
                    WHERE (`presencas`.`data` >= '{$data}' AND `presencas`.`data` < CURDATE())
                    AND `presencas`.`id_alunos` = `alunos`.`id`
                    AND `presencas`.`id_aulas` = {$id_aulas}
                )
            ), 0) AS `assiduidade`
            FROM `alunos_aulas`
            LEFT JOIN `alunos` ON `alunos`.`id` = `alunos_aulas`.`id_alunos`
            LEFT JOIN `aulas` ON `aulas`.`id` = `alunos_aulas`.`id_aulas`
            WHERE `alunos_aulas`.`id_aulas` = {$id_aulas}
            {$dia}
            AND `alunos`.`ativo` = 1
            ORDER BY `alunos`.`nome`
        ");

        return $result;
    }

    function alunosAulas($id_alunos, $id_aulas, $dia, $e)
    {
        $result = $this->mysqli->query("
            SELECT `alunos_aulas`.*, `aulas`.`estudio`
            FROM `alunos_aulas`
            LEFT JOIN `aulas` ON `aulas`.`id` = `alunos_aulas`.`id_aulas`
            LEFT JOIN `alunos` ON `alunos`.`id` = `alunos_aulas`.`id_alunos`
            WHERE `alunos_aulas`.`id_alunos` = {$id_alunos}
            AND `alunos_aulas`.`id_aulas` = {$id_aulas}
            AND `alunos_aulas`.`dia` = '{$dia}'
            AND `aulas`.`estudio` = {$e}
            AND `alunos`.`ativo` = 1
        ");

        return $result;
    }

    function mensalidade($id_alunos, $id_horarios)
    {
        $result = $this->mysqli->query("
            SELECT `alunos_aulas`.*, `aulas`.`id_modalidades`
            FROM `alunos_aulas`
            LEFT JOIN `aulas` ON `aulas`.`id` = `alunos_aulas`.`id_aulas`
            WHERE `alunos_aulas`.`id_alunos` = {$id_alunos}
            AND `aulas`.`id_horarios` = {$id_horarios}
            AND `aulas`.`id_modalidades` != 28 /* Crew */
        ");

        return $result;
    }

    function mensalidadeCrew($id_alunos)
    {
        $result = $this->mysqli->query("
            SELECT `alunos_aulas`.*, `aulas`.`id_modalidades`
            FROM `alunos_aulas`
            LEFT JOIN `aulas` ON `aulas`.`id` = `alunos_aulas`.`id_aulas`
            WHERE `alunos_aulas`.`id_alunos` = {$id_alunos}
            AND (`alunos_aulas`.`id_aulas` = 79 OR `alunos_aulas`.`id_aulas` = 80) /* Crew */
        ");

        return $result;
    }

    function createAlunoAula($id_alunos, $id_aulas, $dia)
    {
        $this->mysqli->query("
            INSERT INTO `alunos_aulas` (`id_alunos`, `id_aulas`, `dia`)
            VALUES ({$id_alunos}, {$id_aulas}, '{$dia}')
        ");
    }

    function removeAlunoAula($id_alunos, $id_aulas, $dia)
    {
        $this->mysqli->query("
            DELETE FROM `alunos_aulas`
            WHERE `id_alunos` = {$id_alunos}
            AND `id_aulas` = {$id_aulas}
            AND `dia` = '{$dia}'
        ");
    }

    function createPresenca($id_alunos, $id_aulas, $dia, $presente, $data)
    {
        if (!$presente) {
            if ($dt >= $hoje) {
                $this->mysqli->query("
                    INSERT INTO `presencas` (`id_alunos`, `id_aulas`, `dia`, `presente`, `data`)
                    VALUES ({$id_alunos}, {$id_aulas}, '{$dia}', {$presente}, '{$data}')
                ");
            }
        } else {
            $dt = date("Y-m-d", strtotime($data));
            $this->mysqli->query("
                UPDATE `presencas` SET `data` = '{$data}', `dia` = '{$dia}', `presente` = 1
                WHERE `id_alunos` = {$id_alunos}
                AND `id_aulas` = {$id_aulas}
                AND `data` LIKE '{$dt}%'
            ");
        }
    }

    function removePresenca($id_alunos, $id_aulas, $dia, $data)
    {
        $data = date("Y-m-d", strtotime($data));
        $this->mysqli->query("
            UPDATE `presencas` SET `presente` = 0
            WHERE `id_alunos` = {$id_alunos}
            AND `id_aulas` = {$id_aulas}
            AND `data` LIKE '{$data}%'
        ");

        /*
        $this->mysqli->query
        ("
            DELETE FROM `presencas`
            WHERE `id_alunos` = {$id_alunos}
            AND `id_aulas` = {$id_aulas}
            AND `dia` = '{$dia}'
            AND `data` LIKE '{$data}%'
        ");
        */
    }

    function aulaData($id_aulas, $data)
    {
        $result = $this->mysqli->query("
            SELECT `aulas_professores`.*
            FROM `aulas_professores`
            WHERE `aulas_professores`.`id_aulas` = {$id_aulas}
            AND `aulas_professores`.`data` = '{$data}'
        ");
        $row = $result->fetch_array();
        if ($row["id"]) {
            return $row["id"];
        }

        return false;
    }

    function presencaAluno($id_aulas, $id_alunos, $data)
    {
        $result = $this->mysqli->query("
            SELECT `presencas`.*
            FROM `presencas`
            WHERE `presencas`.`id_alunos` = {$id_alunos}
            AND `presencas`.`id_aulas` = {$id_aulas}
            AND `presencas`.`data` LIKE '{$data}%'
        ");

        if (!$result->num_rows && $this->aulaData($id_aulas, $data)) {
            $this->createPresenca($id_alunos, $id_aulas, "", 0, $data);
        }

        return $result;
    }

    function presencasAula($id_aulas, $data)
    {
        $data_inicio = $this->dataAssiduidade();

        $result = $this->mysqli->query("
            SELECT `presencas`.`id` AS `id_presencas`, `presencas`.`presente`, `presencas`.`observacoes`, `alunos`.*,
            ROUND((
                (
                    SELECT COUNT(`presencas`.`id`)
                    FROM `presencas`
                    RIGHT JOIN `aulas_professores` ON (`aulas_professores`.`id_aulas` = `presencas`.`id_aulas` AND `aulas_professores`.`data` = DATE_FORMAT(`presencas`.`data`, '%Y-%m-%d'))
                    WHERE (`presencas`.`data` >= '{$data_inicio}' AND `presencas`.`data` < CURDATE())
                    AND `presencas`.`presente` = 1
                    AND `presencas`.`id_alunos` = `alunos`.`id`
                    AND `presencas`.`id_aulas` = {$id_aulas}
                ) * 100 /
                (
                    SELECT COUNT(`presencas`.`id`)
                    FROM `presencas`
                    RIGHT JOIN `aulas_professores` ON (`aulas_professores`.`id_aulas` = `presencas`.`id_aulas` AND `aulas_professores`.`data` = DATE_FORMAT(`presencas`.`data`, '%Y-%m-%d'))
                    WHERE (`presencas`.`data` >= '{$data_inicio}' AND `presencas`.`data` < CURDATE())
                    AND `presencas`.`id_alunos` = `alunos`.`id`
                    AND `presencas`.`id_aulas` = {$id_aulas}
                )
            ), 0) AS `assiduidade`
            FROM `presencas`
            LEFT JOIN `alunos` ON `alunos`.`id` = `presencas`.`id_alunos`
            WHERE `presencas`.`id_aulas` = {$id_aulas}
            AND `presencas`.`data` LIKE '{$data}%'
            ORDER BY `presencas`.`presente` DESC, `alunos`.`nome`
        ");

        return $result;
    }

    function interrupcao($data)
    {
        $result = $this->mysqli->query("
            SELECT COUNT(`interrupcoes`.`id`)
            FROM `interrupcoes`
            WHERE '{$data}' BETWEEN `interrupcoes`.`data_inicio` AND `interrupcoes`.`data_fim`
        ");

        return $result;
    }

    function pedagogia()
    {
        $result = $this->mysqli->query("
            SELECT `pedagogia`.*, `modalidades`.`nome` AS `modalidade`, `professores`.`nome` AS `professor`
            FROM `pedagogia`
            LEFT JOIN `modalidades` ON `modalidades`.`id` = `pedagogia`.`id_modalidades`
            LEFT JOIN `professores` ON `professores`.`id` = `pedagogia`.`id_professores`
        ");

        return $result;
    }

    function alunosPedagogia($id_pedagogia)
    {
        $result = $this->mysqli->query("
            SELECT `alunos_pedagogia`.*, `alunos`.`nome` AS `aluno`
            FROM `alunos_pedagogia`
            LEFT JOIN `alunos` ON `alunos`.`id` = `alunos_pedagogia`.`id_alunos`
            WHERE `alunos_pedagogia`.`id_pedagogia` = {$id_pedagogia}
        ");

        return $result;
    }

    function aulaProfessor(
        $id_professores,
        $id_aulas,
        $dia,
        $data,
        $inicio,
        $fim,
        $valor,
    ) {
        $this->mysqli->query("
            INSERT INTO `aulas_professores` (`id_professores`, `id_aulas`, `dia`, `data`, `inicio`, `fim`, `valor`)
            VALUES ({$id_professores}, {$id_aulas}, '{$dia}', '{$data}', '{$inicio}', '{$fim}', '{$valor}')
        ");
    }

    function aula($id, $data)
    {
        // Validate and sanitize parameters
        $id = (int) $id;
        $data = $this->mysqli->real_escape_string($data);

        if ($id <= 0) {
            return false;
        }

        $result = $this->mysqli->query("
            SELECT `aulas_professores`.*, `modalidades`.`nome` AS `modalidade`, `modalidades`.`abreviatura`, `professores`.`nome` AS `professor`, `professores`.`data_nascimento`, `professores`.`telemovel`
            FROM `aulas_professores`
            LEFT JOIN `aulas` ON `aulas`.`id` = `aulas_professores`.`id_aulas`
            LEFT JOIN `modalidades` ON `modalidades`.`id` = `aulas`.`id_modalidades`
            LEFT JOIN `professores` ON `professores`.`id` = `aulas_professores`.`id_professores`
            WHERE `aulas_professores`.`id_aulas` = {$id}
            AND `aulas_professores`.`data` = '{$data}'
        ");

        return $result;
    }

    function presencasObservacoes($id_presencas, $observacoes)
    {
        $this->mysqli->query("
            UPDATE `presencas` SET `observacoes` = \"{$observacoes}\"
            WHERE `id` = {$id_presencas}
        ");
    }

    function presencasPresente($id_presencas, $presente)
    {
        // Use prepared statement for safety
        $stmt = $this->mysqli->prepare(
            "UPDATE `presencas` SET `presente` = ? WHERE `id` = ?",
        );
        if (!$stmt) {
            error_log("Prepare failed: " . $this->mysqli->error);
            return false;
        }

        $stmt->bind_param("ii", $presente, $id_presencas);
        $result = $stmt->execute();

        if (!$result) {
            error_log("Execute failed: " . $stmt->error);
            return false;
        }

        $affected_rows = $this->mysqli->affected_rows;
        $stmt->close();

        // Log for debugging
        error_log(
            "presencasPresente: id={$id_presencas}, presente={$presente}, affected_rows={$affected_rows}",
        );

        // Return true if the update was successful
        return $result && $affected_rows > 0;
    }

    function apagaPresenca($id_presencas)
    {
        $result = $this->mysqli->query("
            DELETE FROM `presencas`
            WHERE `id` = {$id_presencas}
        ");

        return $result;
    }

    function presencasAluno($id_alunos)
    {
        $data = $this->dataAssiduidade();

        $result = $this->mysqli->query("
            SELECT `presencas`.`id` AS `id_presencas`, DATE_FORMAT(`presencas`.`data`, '%d-%m-%Y') AS `data`, `presencas`.`presente`,
            `modalidades`.`nome` AS `modalidade`, `modalidades`.`abreviatura`, IF(`professores`.`alcunha`, `professores`.`nome`, `professores`.`alcunha`) AS `professor`, `aulas_professores`.`id` AS `id_aulas_professores`, `aulas`.`id` AS `id_aulas`,
            CONCAT(DATE_FORMAT(`aulas_professores`.`inicio`,'%H:%i'), ' > ', DATE_FORMAT(`aulas_professores`.`fim`,'%H:%i')) AS `horario`
            FROM `presencas`
            LEFT JOIN `aulas` ON `aulas`.`id` = `presencas`.`id_aulas`
            LEFT JOIN `modalidades` ON `modalidades`.`id` = `aulas`.`id_modalidades`
            LEFT JOIN `professores` ON `professores`.`id` = `aulas`.`id_professores`
            RIGHT JOIN `aulas_professores` ON (`aulas_professores`.`id_aulas` = `presencas`.`id_aulas` AND `aulas_professores`.`data` = DATE_FORMAT(`presencas`.`data`, '%Y-%m-%d'))
            WHERE (`presencas`.`data` >= '{$data}' AND `presencas`.`data` < CURDATE())
            AND `presencas`.`id_alunos` = {$id_alunos}
            ORDER BY `presencas`.`data` DESC, `aulas`.`inicio`
        ");

        return $result;
    }

    function assiduidadeModalidades()
    {
        $data = $this->dataAssiduidade();

        $result = $this->mysqli->query("
            SELECT `modalidades`.`nome`, `modalidades`.`abreviatura`, SUM(`presencas`.`presente`) AS `presentes`, `aulas_professores`.`id_professores`, (COUNT(`presencas`.`id`) - SUM(`presencas`.`presente`)) AS `ausentes`, COUNT(`presencas`.`id`) AS `total`, ROUND((SUM(`presencas`.`presente`) * 100 / COUNT(`presencas`.`id`)), 0) AS `assiduidade`
            FROM `presencas`
            LEFT JOIN `aulas` ON `aulas`.`id` = `presencas`.`id_aulas`
            LEFT JOIN `modalidades` ON `modalidades`.`id` = `aulas`.`id_modalidades`
            RIGHT JOIN `aulas_professores` ON (`aulas_professores`.`id_aulas` = `presencas`.`id_aulas` AND `aulas_professores`.`data` = DATE_FORMAT(`presencas`.`data`, '%Y-%m-%d'))
            WHERE (`presencas`.`data` >= '{$data}' AND `presencas`.`data` < CURDATE())
            GROUP BY `modalidades`.`id`
            ORDER BY `assiduidade` DESC, `modalidades`.`nome`
        ");

        return $result;
    }

    function alunosLocalidades()
    {
        $result = $this->mysqli->query("
            SELECT `localidade`, COUNT(`id`) AS `alunos`
            FROM `alunos`
            WHERE `ativo` = 1
            GROUP BY `localidade`
            HAVING `alunos` > 1
            ORDER BY `alunos` DESC
        ");

        return $result;
    }

    function clientes($clientes)
    {
        $result = $this->mysqli->query("
            SELECT `alunos`.*
            FROM `alunos`
            WHERE !`alunos`.`id_customers`
            AND `alunos`.`ativo` = 1
        ");
        while ($row = $result->fetch_array()) {
            extract($row);

            if ($clientes[$nif]) {
                $this->mysqli->query("
                    UPDATE `alunos` SET `id_customers` = {$clientes[$nif]["id"]}
                    WHERE `nif` = '{$nif}'
                ");
            } else {
                $this->mysqli->query("
                    UPDATE `alunos` SET `id_customers` = {$clientes[$nome]["id"]}
                    WHERE `nome` = \"{$clientes[$nome]["nome"]}\"
                ");
            }
        }

        /*
        foreach($clientes as $nif => $cliente)
        {
            $this->mysqli->query
            ("
                UPDATE `alunos` SET `id_customers` = {$cliente['id']}
                WHERE `nif` = '{$nif}'
            ");
        */
    }

    function presencasProfessor($id_professor)
    {
        $data = $this->dataAssiduidade();

        $result = $this->mysqli->query("
            SELECT
                SUM(`presencas`.`presente`) AS `presentes`,
                (COUNT(`presencas`.`id`) - SUM(`presencas`.`presente`)) AS `ausentes`,
                COUNT(`presencas`.`id`) AS `total`
            FROM `presencas`
            LEFT JOIN `aulas` ON `aulas`.`id` = `presencas`.`id_aulas`
            RIGHT JOIN `aulas_professores` ON (`aulas_professores`.`id_aulas` = `presencas`.`id_aulas` AND `aulas_professores`.`data` = DATE_FORMAT(`presencas`.`data`, '%Y-%m-%d'))
            WHERE (`presencas`.`data` >= '{$data}' AND `presencas`.`data` < CURDATE())
            AND `aulas`.`id_professores` = {$id_professor}
        ");

        return $result;
    }

    function assiduidadeModalidadesProfessor($id_professor)
    {
        $data = $this->dataAssiduidade();

        $result = $this->mysqli->query("
            SELECT
                `modalidades`.`nome`,
                SUM(`presencas`.`presente`) AS `presentes`,
                (COUNT(`presencas`.`id`) - SUM(`presencas`.`presente`)) AS `ausentes`,
                COUNT(`presencas`.`id`) AS `total`
            FROM `presencas`
            LEFT JOIN `aulas` ON `aulas`.`id` = `presencas`.`id_aulas`
            LEFT JOIN `modalidades` ON `modalidades`.`id` = `aulas`.`id_modalidades`
            RIGHT JOIN `aulas_professores` ON (`aulas_professores`.`id_aulas` = `presencas`.`id_aulas` AND `aulas_professores`.`data` = DATE_FORMAT(`presencas`.`data`, '%Y-%m-%d'))
            WHERE (`presencas`.`data` >= '{$data}' AND `presencas`.`data` < CURDATE())
            AND `aulas`.`id_professores` = {$id_professor}
            GROUP BY `modalidades`.`id`
            HAVING `total` > 0
            ORDER BY `modalidades`.`nome`
        ");

        return $result;
    }

    function eventos()
    {
        $result = $this->mysqli->query("
            SELECT `eventos`.*, `categorias`.`nome` AS `categoria`,
            (SELECT COUNT(`professores_eventos`.`id`) FROM `professores_eventos` WHERE `professores_eventos`.`id_eventos` = `eventos`.`id`) AS `professores`,
            (SELECT COUNT(`alunos_eventos`.`id`) FROM `alunos_eventos` WHERE `alunos_eventos`.`id_eventos` = `eventos`.`id`) AS `alunos`
            FROM `eventos`
            LEFT JOIN `categorias` ON `categorias`.`id` = `eventos`.`id_categorias`
            WHERE `eventos`.`ativo` = 1
            ORDER BY `eventos`.`data` DESC
        ");

        return $result;
    }

    function evento($id_eventos)
    {
        $result = $this->mysqli->query("
            SELECT `eventos`.*, `categorias`.`nome` AS `categoria`
            FROM `eventos`
            LEFT JOIN `categorias` ON `categorias`.`id` = `eventos`.`id_categorias`
            WHERE `eventos`.`id` = '{$id_eventos}' AND `eventos`.`ativo` = 1
        ");
        return $result;
    }

    function alunosEvento($id_eventos)
    {
        $result = $this->mysqli->query("
            SELECT a.*, ae.id as id_aluno_evento, ae.presente
            FROM alunos a
            LEFT JOIN alunos_eventos ae ON ae.id_alunos = a.id AND ae.id_eventos = '{$id_eventos}'
            WHERE a.ativo = 1
            ORDER BY a.nome ASC
        ");
        return $result;
    }

    function alunosEventoRegistrados($id_eventos)
    {
        $result = $this->mysqli->query("
            SELECT a.*, ae.id as id_aluno_evento, ae.presente
            FROM alunos a
            INNER JOIN alunos_eventos ae ON ae.id_alunos = a.id AND ae.id_eventos = '{$id_eventos}'
            WHERE a.ativo = 1
            ORDER BY a.nome ASC
        ");
        return $result;
    }

    function presencaEventoAluno($id_eventos, $id_alunos)
    {
        $result = $this->mysqli->query("
            SELECT * FROM `alunos_eventos`
            WHERE `id_eventos` = '{$id_eventos}' AND `id_alunos` = '{$id_alunos}'
        ");
        return $result;
    }

    function createPresencaEvento($id_alunos, $id_eventos, $presente)
    {
        // Check if the required columns exist in the table
        $columns_exist = $this->checkAlunosEventosColumns();

        if (!$columns_exist["presente"] || !$columns_exist["destaque"]) {
            // Add missing columns if they don't exist
            $this->addMissingAlunosEventosColumns($columns_exist);

            // Refresh column check
            $columns_exist = $this->checkAlunosEventosColumns();
        }

        // First, check if the event has destaque = 1
        $destaque = 0;
        $result = $this->mysqli->query("
            SELECT `destaque` FROM `eventos`
            WHERE `id` = '{$id_eventos}' AND `ativo` = 1
        ");
        if ($result && ($row = $result->fetch_array())) {
            $destaque = $row["destaque"];
        }

        if ($columns_exist["presente"] && $columns_exist["destaque"]) {
            // Use INSERT ... ON DUPLICATE KEY UPDATE if columns exist
            $this->mysqli->query("
                INSERT INTO `alunos_eventos` (`id_alunos`, `id_eventos`, `presente`, `destaque`)
                VALUES ('{$id_alunos}', '{$id_eventos}', '{$presente}', '{$destaque}')
                ON DUPLICATE KEY UPDATE
                `presente` = VALUES(`presente`),
                `destaque` = VALUES(`destaque`)
            ");
        } else {
            // Fallback to basic insert without new columns
            error_log(
                "Warning: Using fallback insert for alunos_eventos - columns missing",
            );

            // Check if record exists
            $check_result = $this->mysqli->query("
                SELECT id FROM `alunos_eventos`
                WHERE `id_alunos` = '{$id_alunos}' AND `id_eventos` = '{$id_eventos}'
            ");

            if ($check_result->num_rows == 0) {
                // Insert new record
                $this->mysqli->query("
                    INSERT INTO `alunos_eventos` (`id_alunos`, `id_eventos`)
                    VALUES ('{$id_alunos}', '{$id_eventos}')
                ");
            }
            // If record exists, we consider it "present" by default
        }

        // Check for MySQL errors
        if ($this->mysqli->error) {
            error_log(
                "MySQL Error in createPresencaEvento: " . $this->mysqli->error,
            );
            return false;
        }

        // Check if operation was successful
        // For ON DUPLICATE KEY UPDATE, affected_rows can be 0 if no change was needed
        // This is actually a success case, not a failure
        if ($this->mysqli->error) {
            return false;
        }

        // If we get here, the query executed without errors
        // affected_rows = 0 means record exists with same values (success)
        // affected_rows = 1 means new record inserted (success)
        // affected_rows = 2 means existing record updated (success)
        return true;
    }

    private function checkAlunosEventosColumns()
    {
        $columns = ["presente" => false, "destaque" => false];

        $result = $this->mysqli->query("DESCRIBE alunos_eventos");
        if ($result) {
            while ($row = $result->fetch_array()) {
                $field_name = $row["Field"];
                if (isset($columns[$field_name])) {
                    $columns[$field_name] = true;
                }
            }
        }

        return $columns;
    }

    private function addMissingAlunosEventosColumns($columns_exist)
    {
        try {
            // Add presente column if missing
            if (!$columns_exist["presente"]) {
                $this->mysqli->query("
                    ALTER TABLE alunos_eventos
                    ADD COLUMN presente TINYINT(1) NOT NULL DEFAULT 1
                ");
                if ($this->mysqli->error) {
                    error_log(
                        "Error adding 'presente' column: " .
                            $this->mysqli->error,
                    );
                } else {
                    error_log(
                        "Successfully added 'presente' column to alunos_eventos",
                    );
                }
            }

            // Add destaque column if missing
            if (!$columns_exist["destaque"]) {
                $this->mysqli->query("
                    ALTER TABLE alunos_eventos
                    ADD COLUMN destaque TINYINT(1) NOT NULL DEFAULT 0
                ");
                if ($this->mysqli->error) {
                    error_log(
                        "Error adding 'destaque' column: " .
                            $this->mysqli->error,
                    );
                } else {
                    error_log(
                        "Successfully added 'destaque' column to alunos_eventos",
                    );
                }
            }

            // Add unique constraint if both columns exist
            if (!$columns_exist["presente"] || !$columns_exist["destaque"]) {
                // Check if unique constraint already exists
                $constraint_result = $this->mysqli->query("
                    SHOW INDEX FROM alunos_eventos WHERE Key_name = 'unique_student_event'
                ");

                if ($constraint_result && $constraint_result->num_rows == 0) {
                    // Remove duplicates first
                    $this->mysqli->query("
                        DELETE ae1 FROM alunos_eventos ae1
                        INNER JOIN alunos_eventos ae2
                        WHERE ae1.id > ae2.id
                        AND ae1.id_alunos = ae2.id_alunos
                        AND ae1.id_eventos = ae2.id_eventos
                    ");

                    // Add unique constraint
                    $this->mysqli->query("
                        ALTER TABLE alunos_eventos
                        ADD CONSTRAINT unique_student_event
                        UNIQUE KEY (id_alunos, id_eventos)
                    ");

                    if ($this->mysqli->error) {
                        error_log(
                            "Error adding unique constraint: " .
                                $this->mysqli->error,
                        );
                    } else {
                        error_log(
                            "Successfully added unique constraint to alunos_eventos",
                        );
                    }
                }
            }
        } catch (Exception $e) {
            error_log(
                "Exception in addMissingAlunosEventosColumns: " .
                    $e->getMessage(),
            );
        }
    }

    function removePresencaEvento($id_alunos, $id_eventos)
    {
        // DEPRECATED: This function is no longer used in the new presence logic
        // We now maintain records for both present and absent states
        // Use createPresencaEvento($id_alunos, $id_eventos, 0) instead
        // First check if record exists
        $check_result = $this->mysqli->query("
            SELECT id FROM `alunos_eventos`
            WHERE `id_alunos` = '{$id_alunos}' AND `id_eventos` = '{$id_eventos}'
        ");

        if ($check_result->num_rows == 0) {
            // Record doesn't exist, so it's already "absent" - return true
            return true;
        }

        $this->mysqli->query("
            DELETE FROM `alunos_eventos`
            WHERE `id_alunos` = '{$id_alunos}' AND `id_eventos` = '{$id_eventos}'
        ");

        // Check for MySQL errors
        if ($this->mysqli->error) {
            error_log(
                "MySQL Error in removePresencaEvento: " . $this->mysqli->error,
            );
            return false;
        }

        return $this->mysqli->affected_rows > 0;
    }

    function professor($id_professores)
    {
        $result = $this->mysqli->query("
            SELECT `professores`.*
            FROM `professores`
            WHERE `professores`.`id` = {$id_professores}
        ");

        return $result;
    }

    function professoresEvento($id_eventos)
    {
        $result = $this->mysqli->query("
            SELECT p.*, pe.id as id_professor_evento
            FROM professores p
            LEFT JOIN professores_eventos pe ON pe.id_professores = p.id AND pe.id_eventos = '{$id_eventos}'
            WHERE p.ativo = 1
            ORDER BY p.nome ASC
        ");
        return $result;
    }

    function createProfessorEvento($id_professores, $id_eventos)
    {
        // Check if already exists
        $check = $this->mysqli->query("
            SELECT id FROM professores_eventos
            WHERE id_professores = '{$id_professores}' AND id_eventos = '{$id_eventos}'
        ");

        if ($check->num_rows > 0) {
            return true; // Already exists
        }

        $this->mysqli->query("
            INSERT INTO professores_eventos (id_professores, id_eventos)
            VALUES ('{$id_professores}', '{$id_eventos}')
        ");
        return $this->mysqli->affected_rows > 0;
    }

    function removeProfessorEvento($id_professores, $id_eventos)
    {
        $this->mysqli->query("
            DELETE FROM professores_eventos
            WHERE id_professores = '{$id_professores}' AND id_eventos = '{$id_eventos}'
        ");
        return $this->mysqli->affected_rows > 0;
    }
}

?>
