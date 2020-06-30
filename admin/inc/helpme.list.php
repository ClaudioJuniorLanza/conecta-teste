<b>Help me! - Listagem v1.0:</b><br/>
<hr/>
Substitua o helpme pelo seguinte código:<br/>
<pre>
// Opcional:
$usuarObj = dUsuario::isLoggedOrRedirect();
$usuarObj->checkPermsOrDie('PERMISSAO_DESEJADA');

$showTrashBin = isset($_GET['trashBin']);

dAL::layTop(Array('bodyTitle'=>"Gerenciar [OBJETO]", 'saveGoBack'=>true));
dAL::goBack(false, "<a href='[OBJETO]_edit.php'>Cadastrar novo [OBJETO]</a>");

dHelper2::includePage('inc/dAL.SearchBox2.inc.php');

dAL::layBottom();
</pre>
