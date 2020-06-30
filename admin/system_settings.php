<?php
require_once "config.php";
require_once "template.php";

$usuarObj = dUsuario::isLogged();
$usuarObj->checkPermsOrDie('MASTER_SETTINGS');

$allSettings = dConfiguracao::getConfigList();
$exModules = dConfiguracao::getConfig('CORE/MODULES');
$exModules = $exModules ? explode(",", $exModules) : false;
if ($exModules) {
    foreach ($exModules as $exModule) {
        $exModule::modCreateConfig($allSettings);
    }
}

dAL::layTop(array('bodyTitle' => "Configurações do sistema"));

$isTableOpen = false;
foreach ($allSettings as $settingKey => $item) {
    if (!is_array($item)) {
        if ($isTableOpen) {
            echo "</table>";
            $isTableOpen = false;
        }
        echo "<div style='background: #DDF; font-weight: bold; padding: 5px'>{$item}</div>";
        echo "<table cellpadding='3' style='font: 12px Arial'>";
        $isTableOpen = true;
        continue;
    }
    if (!$isTableOpen) {
        echo "<table cellpadding='3' style='font: 12px Arial'>";
    }

    echo "<tr>";
    if ($item[0] == 'cbox') {
        echo "<td colspan='2' bgcolor='#EEEEEE' title='{$settingKey}'>";
        echo dInput2::checkbox("name='{$settingKey}' onclick='changeCbOk(this)'", dConfiguracao::getConfig($settingKey),
            $item[1]);
        echo "</td>";
    } else {
        echo "	<td bgcolor='#EEEEEE' title='{$settingKey}'>{$item[1]}</td>";
        echo "	<td>" . dInput2::inputRead("name='{$settingKey}' size='50'", dConfiguracao::getConfig($settingKey),
                'changeOk') . "</td>";
    }
    echo "</tr>";
}
if ($isTableOpen) {
    $isTableOpen = false;
    echo "</table>";
}
?>
    <script type='text/javascript'>
        function changeOk(uid, name, nv, ov) {
            var input = $("#dIROText" + uid);
            var span = $("#dIROSpan" + uid);
            $.post("ajax.system_settings.php", {key: name, nv: nv}, function (ret) {
                var newVal = ov;
                if (ret.substr(0, 3) == 'OK:') {
                    span.html("<font color='#00AA00'>Informação gravada com sucesso!</font>");
                    newVal = ret.substr(3);
                } else if (ret.substr(0, 5) == 'ERRO:') {
                    span.html("<font color='#FF0000'><b>ERRO:</b> " + ret.substr(5) + "</font>");
                    newVal = ov;
                } else {
                    span.html(ret);
                }
                setTimeout(function () {
                    span.html(newVal);
                    input.val(newVal);
                }, 3000);
            });
            return true;
        }

        function changeCbOk(obj) {
            $.post("ajax.system_settings.php", {key: obj.name, nv: obj.checked ? '1' : '0'}, function (ret) {
                obj.disabled = false;
            });
            obj.disabled = true;
            return true;
        }
    </script>
<?php
dAL::layBottom();
