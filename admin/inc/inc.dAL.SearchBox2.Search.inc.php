<div class="dalSearchBox">
    <form method='get' action='<?= $_SERVER['PHP_SELF'] ?>' style='display: inline'>
        <? if ($_GET) foreach ($_GET as $idx => $val):
            if (in_array($idx, explode("|", "q|x|y|c"))) {
                continue;
            }
            ?>
            <input type='hidden' name='<?= $idx ?>' value="<?= htmlspecialchars($val) ?>"/>
        <? endforeach ?>
        <table cellpadding="2" cellspacing="0" class='q_box'>
            <tr>
                <td style='padding-left: 25px'>Buscar por:</td>
                <td><input type="text" name="q" class='q' style="width: 100%; border: 1px solid #EEE"
                           value="<?= htmlspecialchars(@$_GET['q']) ?>"/></td>
                <td align="center"><input type='image' border="0" style="border: 0" src="images/bt_ok.gif"
                                          align="bottom"></td>
            </tr>
        </table>
    </form>
</div>
