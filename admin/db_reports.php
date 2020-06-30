<?php
$_DisableAuth = isset($_GET['token']);
require_once "config.php";
require_once "template.php";

set_time_limit(10);

dAuditAcesso::autoLog(false);

if (isset($_GET['token'])) {
    if (!@$_GET['token']) {
        die("No token.");
    }

    $tokenObj = cToken::load($_GET['token'], [
        'useAsPrimaryKey' => 'token',
        'loadExt' => 'usuarObj',
    ]);
    if (!$tokenObj) {
        header("HTTP/1.0 404 Not Found");
        die;
    }

    $forExcel = true;
    if ($tokenObj->v('query_string') != "#") {
        parse_str($tokenObj->v('query_string'), $_GET);
    } else {
        $_GET = [];
    }

    // Finge que está logado, para passar por isLoggedOrRedirect()
    dUsuario::$Scache['logged'] = $tokenObj->v('usuarObj');
}
dUsuario::isLoggedOrRedirect();

$dbDados = dDatabase::start('dbDados');
$dbDados->setCharset('utf8');
$dbDados->setConfig(
    dSystem::getGlobal('dbHost'),
    'conecta_sementes',
    'j(*Eg98hwe4uoa',
    'conectasementes',
    dSystem::getGlobal('dbEngine')
);

$LIMIT_FOR_DESKTOP = 1250;
$LIMIT_FOR_EXCEL = 10000;

$dropDbList = $dbDados->singleColumn("show tables");
$tableName = @$_GET['t'];

// Nenhuma tabela selecionada:
if (!in_array($tableName, $dropDbList)) {
    dAL::layTop(['bodyTitle' => "Gerenciar Base de Dados",]);

    echo "<h2>Selecione a base de dados:</h2>";
    echo "<ul>";
    foreach ($dropDbList as $tableName) {
        echo "<li><a href='{$_SERVER['PHP_SELF']}?t={$tableName}' style='padding: 4px 8px 4px 0; display: inline-block'>" . substr($tableName,
                3) . "</a></li>";
    }
    echo "</ul>";

    dAL::layBottom();
    die;
}

// O que varia entre as tabelas/lógicas?
// --> Colunas principais
// --> Filtros
// --> Não contratado: Tabelas relacionadas
$allColumns = $dbDados->singleQuery("show fields from {$tableName}");
$allColumns = call_user_func(function ($allColumns) {
    $ret = [];
    foreach ($allColumns as $idx => $row) {
        if ($row['Field'] == 'id') {
            continue;
        }
        $format = preg_replace("/\(.+$/", "", $row['Type']);
        $format = preg_match("/char/", $format) ? "char" : "number";

        $ret[$row['Field']] = [$row['Field'], false, 'format' => $format];
    }
    return $ret;
}, $allColumns);

// Lógica para o relatório.
$exFilters = isset($_GET['f']) ? $_GET['f'] : [];
$exColumns = isset($_GET['c']) ? $_GET['c'] : array_keys($allColumns);
$exColumns = array_intersect($exColumns, array_keys($allColumns));

$filters = [];
foreach ($exFilters as $_row) {
    if (in_array($_row['con'], ['co', 'nco'])) { // Contains
        $_not = ($_row['con'][0] == 'n') ? "NOT " : "";
        $filters[] = "{$_row['col']} {$_not} LIKE '%" . addslashes($_row['val']) . "%'";
    } elseif (in_array($_row['con'], ['iw', 'niw'])) { // Init with
        $_not = ($_row['con'][0] == 'n') ? "NOT " : "";
        $filters[] = "{$_row['col']} {$_not} LIKE '" . addslashes($_row['val']) . "%'";
    } elseif (in_array($_row['con'], ['ew', 'new'])) { // Ends with
        $_not = ($_row['con'][0] == 'n') ? "NOT " : "";
        $filters[] = "{$_row['col']} {$_not} LIKE '%" . addslashes($_row['val']) . "'";
    } elseif (in_array($_row['con'], ['eq', 'neq'])) {
        $_op = ($_row['con'] == 'eq') ? "=" : "!=";
        $filters[] = "{$_row['col']} {$_op} '" . addslashes($_row['val']) . "'";
    } elseif (in_array($_row['con'], ['gt', 'lt'])) {
        // Greater than | Lower than
        $_val = dHelper2::forceFloat($_row['val']);
        $_op = ($_row['con'] == 'gt') ? ">=" : "<=";
        $filters[] = "{$_row['col']} {$_op} '{$_val}'";
    } else {
        die("Unknown condition: {$_row['con']})");
    }

    $filters[] = $_row['next'];
}
array_pop($filters); // Remove o último (and/or)

$excelAsAttachment = (@$_GET['as_xls']);
$forExcel = ($excelAsAttachment || isset($forExcel)); // Pode vir definido em report_token.php

// Vamos processar as buscas.
$LIMIT = ($forExcel ? $LIMIT_FOR_EXCEL : $LIMIT_FOR_DESKTOP);

$query = [];
$query[] = "SELECT\tid,";
$query[] = "\t" . implode(",\r\n\t", $exColumns);
$query[] = "FROM  \t{$tableName}";
if ($filters) {
    $query[] = "WHERE\r\n\t" . implode("\r\n\t", $filters);
}
$query[] = "LIMIT " . ($LIMIT);
$results = $dbDados->singleQuery(implode("\r\n", $query));

if ($forExcel) {
    if ($excelAsAttachment) {
        $useFileName = "{$tableName} " . date('d-m-Y H\hi\m') . ".xls";
        header('Content-Description: File Transfer');
        header("Content-Transfer-Encoding: binary");
        header("Content-Type: application/force-download");
        header("Content-Disposition: attachment; filename=\"" . str_replace("+", " ", urlencode($useFileName)) . "\"");
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
    }

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head>';
    echo '<meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">';
    echo '<meta charset="utf-8" />';
    echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
    echo '</head>';
    echo '<body>';
    if (!$excelAsAttachment) {
        echo "	<h2>Resultados do seu Token</h2>";
    }
} else {
    dAL::layTop([
        'bodyTitle' => dHelper2::ucwordsBr(strtr(substr($tableName, 3), "_", " ")),
        'breadCrumbs' => ["<a href='db_reports.php'>Banco de Dados</a>", $tableName],
        'leftMenu' => false,
        'goBack' => [
            "<a href='db_edit.php?t={$tableName}'>Cadastrar novo item</a>",
            "Base de Dados: " .
            dInput2::select(
                "onchange=\"location.href='db_reports.php?t='+this.value;\"",
                array_map(function ($tableName) {
                    return [$tableName, substr($tableName, 3)];
                }, $dropDbList),
                $tableName
            ),
        ],
    ]);
}

?>
<? if (!$forExcel): ?>
    <form method='GET'>
        <input type='hidden' name='t' value="<?= $tableName ?>"/>
        <div style='display: flex'>
            <div style="background: #EEE; border: 1px solid #888; padding: 12px; white-space: nowrap">
                <div style='display: block; margin-bottom: 4px; font-weight: bold;'>Selecione as colunas para exibir:
                </div>
                <table id='columnList' width='100%'>
                    <? foreach ($allColumns as $colKey => $colInfo): ?>
                        <tr>
                            <td nowrap='nowrap'><?= dInput2::checkbox("name='c[]' value='{$colKey}'",
                                    in_array($colKey, $exColumns), " " . (!$colInfo[1] ?
                                        $colInfo[0] :
                                        "{$colInfo[0]} <a href='#' onclick=\"alert('" . addslashes($colInfo[1]) . "'); return false;\" class='fa fa-question-circle' style='text-decoration: none'></a>"));
                                ?></td>
                        </tr>
                    <? endforeach ?>
                </table>
            </div>
            <div class="columns" style="margin-left: 20px; align-self: flex-start">
                <div style="background: #e6eede; border: 1px solid #888; padding: 12px; margin-bottom: 16px">
                    <div style='display: block; margin-bottom: 4px; font-weight: bold;'>Selecione os filtros:</div>
                    <table cellpadding='4'>
                        <tr class='sample' style='display: none'>
                            <td><?= dInput2::select("name='f[_id_][col]' disabled='disabled'",
                                    array_keys($allColumns)); ?></td>
                            <td>
                                <?= dInput2::select("name='f[_id_][con]' disabled='disabled' class='optionsStr'", [
                                    ['eq', "Exatamente igual a..."],
                                    ['iw', "Começa com..."],
                                    ['ew', "Termina com..."],
                                    ['co', "Contém..."],

                                    ['neq', "É diferente de..."],
                                    ['niw', "Não começa com..."],
                                    ['new', "Não termina com..."],
                                    ['nco', "Não contém..."],

                                    ['gt', "Número menor que..."],
                                    ['lt', "Número maior que..."],
                                ]) ?>
                            <td><?= dInput2::input("name='f[_id_][val]' disabled='disabled'"); ?></td>
                            <td><?= dInput2::select("name='f[_id_][next]' disabled='disabled'",
                                    ['and' => 'e...', 'or' => 'ou...']); ?></td>
                            <td style='font-size: 12px'>
                                <a href="#" class='btnRemoveFilter' style='text-decoration: none;'><i
                                            class='fa fa-times'></i> Remover Filtro</a>
                            </td>
                        </tr>
                        <? foreach ($exFilters as $fIdx => $fRow): ?>
                            <tr>
                                <td><?= dInput2::select("name='f[{$fIdx}][col]' disabled='disabled'",
                                        array_keys($allColumns), $fRow['col']); ?></td>
                                <td>
                                    <?= dInput2::select("name='f[{$fIdx}][con]' disabled='disabled' class='optionsStr'",
                                        [
                                            ['eq', "Exatamente igual a..."],
                                            ['iw', "Começa com..."],
                                            ['ew', "Termina com..."],
                                            ['co', "Contém..."],

                                            ['neq', "É diferente de..."],
                                            ['niw', "Não começa com..."],
                                            ['new', "Não termina com..."],
                                            ['nco', "Não contém..."],

                                            ['iw', "Número menor que..."],
                                            ['ew', "Número maior que..."],
                                        ], $fRow['con']) ?>
                                <td><?= dInput2::input("name='f[{$fIdx}][val]' disabled='disabled'",
                                        $fRow['val']); ?></td>
                                <td><?= dInput2::select("name='f[{$fIdx}][next]' disabled='disabled'",
                                        ['and' => 'e...', 'or' => 'ou...'], $fRow['next']); ?></td>
                                <td style='font-size: 12px'>
                                    <a href="#" class='btnRemoveFilter' style='text-decoration: none;'><i
                                                class='fa fa-times'></i> Remover Filtro</a>
                                </td>
                            </tr>
                        <? endforeach; ?>
                    </table>
                    <a href='#' class='btnAddFilter'
                       style='display: inline-block; padding: 4px; text-decoration: none;'><i
                                class='fa fa-plus-circle'></i> Adicionar filtro</a><br/>
                    <br/>
                    <button style='padding: 16px'><i class='fa fa-caret-down'></i> Confirmar Busca <i
                                class='fa fa-caret-down'></i></button>
                </div>


                <script type='text/javascript'>
                    var allColumns = <?=json_encode($allColumns, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>

                        $(function () {
                            var jqoSample = $(".sample");
                            var jqoTable = jqoSample.closest('table');
                            jqoSample.hide();

                            var _refresh = function () {
                                var jqoNotSample = $("tr", jqoTable).not(jqoSample);

                                jqoNotSample.find("input,select").prop('disabled', false);
                                jqoNotSample.find("select[name*=next]").show();
                                jqoNotSample.last().find("select[name*=next]").hide();
                            };

                            jqoTable.on('click', '.btnRemoveFilter', function () {
                                $(this).closest('tr').remove();
                                _refresh();
                                return false;
                            });
                            var _rowId = <?=sizeof($exFilters)?>;
                            $(".btnAddFilter").click(function () {
                                var jqoNewRow = jqoSample.clone();
                                $("[name*=_id_]", jqoNewRow).each(function () {
                                    var myName = $(this).attr('name');
                                    $(this).attr('name', myName.replace('_id_', _rowId));
                                });

                                _rowId++;
                                jqoTable.append(jqoNewRow.show());
                                _refresh();
                                return false;
                            });

                            _refresh();
                        })
                </script>


                <div style="background: #FFFFCC; border: 1px solid #888; padding: 12px">
                    <b>Integração com Excel</b><br/>
                    <small>(Execute a busca antes, e selecione uma opção abaixo)</small>
                    <ul>
                        <a href="<?= dHelper2::changeUrl(['as_xls' => true]) ?>"
                           style="text-decoration: none; color: #00F"><i class='fa fa-file-excel-o'></i> Download como
                            Planilha</a>
                    </ul>
                    <b>Buscas Salvas / Integração via Token:</b> <a href="#" onclick="openXlsHelp(); return false"><i
                                class='fa fa-question-circle'></i></a><br/>
                    <?php
                    dHelper2::includePage("ajax.report_token.php");
                    ?>
                </div>
            </div>
        </div>
    </form>
    <hr/>
<? endif ?>
<?php
if ($results) {
    if (sizeof($results) >= $LIMIT) {
        echo "<div style='background: #FCC; padding: 16px'>";
        echo "  <b>Muitos resultados!</b> - Limitando a exibição para os primeiros " . number_format($LIMIT, 0, ",",
                ".") . " resultados.<br />";
        if ($LIMIT == $LIMIT_FOR_DESKTOP) {
            echo "<i>";
            echo "	<a href='" . dHelper2::changeUrl(['as_xls' => true]) . "' style='text-decoration: none; color: #00F'><i class='fa fa-file-excel-o'></i> Download como Planilha</a>";
            echo "  pode exibir até " . number_format($LIMIT_FOR_EXCEL, 0, '.', '.') . " resultados.";
            echo "</i>";
        } else {
            echo "<i>Filtre melhor a sua busca para conseguir exibir todos os resultados.</i><br />";
        }
        echo "</div>";
        echo "<br />";
    } else {
        echo "<b>" . number_format(sizeof($results), 0, ',', '.') . " resultados encontrados...</b><br />";
        echo "<br/ >";
    }

    echo "<style> .display_results td { font-size: 12px } </style>";
    echo "<table cellpadding='4' cellspacing='0' border='1' style='border-collapse: collapse; white-space: nowrap' class='display_results'>";
    echo "	<thead>";
    echo "		<tr style='background: #DDD'>";
    foreach ($exColumns as $colKey) {
        $colInfo = &$allColumns[$colKey];
        echo "<td nowrap='nowrap'>{$colInfo[0]}</td>";
    }
    echo "		</tr>";
    echo "	</thead>";
    echo "	<tbody>";
    foreach ($results as $rowIdx => $item) {
        echo "<tr>";
        foreach ($exColumns as $colKey) {
            $colInfo = &$allColumns[$colKey];
            $useValue = $item[$colKey];

            echo "<td>";
            echo htmlspecialchars($useValue);
            echo "</td>";
        }
        if (!$forExcel) {
            echo "<td>";
            echo "	<a href='db_edit.php?t={$tableName}&id={$item['id']}' target='_blank'>Editar</a>";
            echo "</td>";
        }
        echo "</tr>";
    }
    echo "	</tbody>";
    echo "</table>";
} else {
    echo "<b>Nenhum resultado.</b><br />";
}

if (isset($forExcel)) {
    echo "</body>";
    echo "</html>";

    if ($excelAsAttachment) {
        ob_end_flush();
    }
} else {
    ?>
    <script>

    </script>
    <?php
    // echo "<script> $(function(){ $(\"#columnList\").dRowDrag(); }); </script>";
    dAL::layBottom();
}

