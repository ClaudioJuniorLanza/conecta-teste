<?php
require_once "config.php";
require_once "template.php";

$usuarObj = cUsuario::isLoggedOrRedirect(true);
$usuarObj->isAgenteOrRedirect();

$clienObj = false;
if (@$_GET['cnpj_ou_renasem']) {
    // Veio pré-filled:
    $isRenasem = dHelper2::formataRenasem($_GET['cnpj_ou_renasem']);
    $clienObj = ($isRenasem) ?
        cUsuario::load($isRenasem, 'renasem') :
        cUsuario::load(preg_replace("/[^0-9]/", "", $_GET['cnpj_ou_renasem']), 'cpf_cnpj');

    if ($clienObj) {
        // Cliente encontrado!
        if ($clienObj->v('agente_id')) {
            // Só vou aceitar se for órfão ou se for meu mesmo.
            $usuarObj->isAgenteOfOrDie($clienObj);
        }
    } else {
        // Novo usuário, vamos pré-preencher o CNPJ ou Renasem.
        $clienObj = new cUsuario;
        ($isRenasem) ?
            $clienObj->v('renasem', $isRenasem) :
            $clienObj->v('cpf_cnpj', $_GET['cnpj_ou_renasem']);
    }
} elseif (is_numeric(@$_GET['id'])) {
    $clienObj = cUsuario::load($_GET['id']);
    if (!$clienObj) {
        // Cliente não encontrado (por ?id=...)
        dHelper2::redirectTo("agente_clientes.php");
        die;
    }

    // Como informou o ID do cliente, é obrigatório que seja dele.
    if (!$usuarObj->isAgenteOf($clienObj)) {
        dHelper2::redirectTo("agente_clientes.php");
        die;
    }
}

$isNewSplashScreen = (@$_GET['add'] == 'new');
if (!$isNewSplashScreen) {
    // Não é Splash Screen (tela onde só é questionado CNPJ ou RENASEM).
    if (!$clienObj) {
        dHelper2::redirectTo("agente_clientes.php");
        die;
    }
    if ($clienObj->isLoaded() && !$clienObj->isComerciante()) {
        // Não pode editar alguém que não é comerciante, logicamente.
        dHelper2::redirectTo("agente_clientes.php");
        die;
    }

    $errorList = [];
    $isUpdate = $clienObj->isLoaded();
    if ($_POST) {
        // Busca por Renasem 1º, seguido de Busca por CNPJ.
        // Se houver renasem e cnpj diferentes, interrompe imediatamente.

        // Quais campos são esperados do formulário?
        $allowForNew = ['cpf_cnpj'];
        $allowAlways = [
            'nome',
            'responsavel_nome',
            'fone1',
            'email',
            'categoria',
            'rg_ie',
            'data_nasc',
            'estado_civil',
            'uf',
            'cidade',
            'bairro',
            'endereco',
            'dados_bancarios',
        ];

        if (!$clienObj->v('renasem') && @$_POST['renasem']) {
            $clienObj->v('renasem', $_POST['renasem']);
        }

        $clienObj->loadArray($_POST, ['onlyKeys' => $allowAlways]);
        if (!$isUpdate) {
            // A senha é obrigatória, então vamos gerar uma senha aleatória de 12 a 14 dígitos.
            $clienObj->loadArray($_POST, ['onlyKeys' => $allowForNew]);
            $clienObj->v('tipo', (strlen($clienObj->v('cpf_cnpj')) < 12) ? 'pf' : 'pj');
            $clienObj->v('senha', call_user_func(function () {
                $_len = rand(10, 14);
                $final = "";
                for ($x = 0; $x < $_len; $x++) {
                    $_r = rand(1, 3); // letra minuscula, letra maiuscula, numero
                    if ($_r == 1) {
                        $final .= chr(rand(97, 122));
                    } // Letra minuscula
                    elseif ($_r == 2) {
                        $final .= chr(rand(65, 90));
                    } // Letra maiscula
                    elseif ($_r == 3) {
                        $final .= rand(0, 9);
                    }         // Numero
                }
                return $final;
            }));
        }

        $clienObj->v('agente_id', $usuarObj->v('id'));
        $clienObj->v('tipo', 'pj');
        $clienObj->v('comerciante_sem_renasem', $clienObj->v('renasem') ? '0' : '1');

        $clienObj->valPassed('clienteRepresentado');
        $_list1 = $clienObj->listErrors(true);

        $clienObj->valPassed('final');
        $_list2 = $clienObj->listErrors(true);
        $errorList = array_merge($_list1 ? $_list1 : [], $_list2 ? $_list2 : []);

        if (!$errorList && $clienObj->save()) {
            if (@$_GET['gotoAnuncio']) {
                $usuarObj->agenteActAs($clienObj);
                dHelper2::redirectTo("ver-anuncio.php?codigo={$_GET['gotoAnuncio']}");
                die;
            }

            dHelper2::redirectTo("agente_clientes.php?updated=" . $clienObj->v('id'));
            die;
        }
    }
}

layCima("Gerenciar clientes", [
    'menuSel' => 'agente_clientes',
    'extraCss' => ['agente'],
]); ?>
    <div class="agente">
        <div class="backLine">
            <a href="agente_clientes.php"><i class='fa fa-caret-left'></i> Voltar</a>
        </div>

        <? if ($isNewSplashScreen): ?>
            <form class="grayForm" method='get'>
                <div class="title">Novo Cliente</div>
                <input type="hidden" name="gotoAnuncio" value="<?= @$_GET['gotoAnuncio'] ?>">
                <div class="form">
                    <div class="row">
                        <span>CPF, CNPJ ou RENASEM:</span>
                        <div>
                            <input name='cnpj_ou_renasem'/><br/>
                        </div>
                    </div>
                </div>
                <button>Continuar</button>
            </form>
        <? else: ?>
            <? if ($errorList): ?>
                <div class="displayErrorBox">
                    <b>Verifique os seguintes problemas:</b><br/>
                    - <?= implode("<br />- ", $errorList); ?>
                </div>
            <? endif ?>

            <form class="grayForm" method='post'
                  action="agente_cliente_edit.php?id=<?= @$_GET['id'] ?>&cnpj_ou_renasem=<?= @$_GET['cnpj_ou_renasem'] ?>&gotoAnuncio=<?= @$_GET['gotoAnuncio'] ?>">
                <div class="title"><?= $clienObj->isLoaded() ? "Editar cliente" : "Cadastrar cliente" ?></div>
                <div class="form">
                    <div class="row">
                        <span>Razão Social:</span>
                        <div><?= dInput2::input("name='nome'", $clienObj); ?></div>
                    </div>
                    <div class="row">
                        <span>Nome do Responsável:</span>
                        <div><?= dInput2::input("name='responsavel_nome'", $clienObj); ?></div>
                    </div>
                    <div class="row">
                        <span>Telefone:</span>
                        <div><?= dInput2::input("name='fone1'", $clienObj, 'fone'); ?></div>
                    </div>
                    <div class="row">
                        <span>E-mail:</span>
                        <div><?= dInput2::input("name='email'", $clienObj); ?></div>
                    </div>
                    <div class="row">
                        <span>Tipo de Atividade:</span>
                        <div><?= dInput2::select("name='categoria'", "Agricultor,Revenda,Grupo de Compra,Outros",
                                $clienObj, false, "Selecione"); ?></div>
                    </div>
                    <div class="row">
                        <span>Renasem:</span>
                        <div>
                            <?= ($isUpdate && $clienObj->v('renasem')) ?
                                htmlspecialchars($clienObj->v('renasem')) : // Já existia - não pode mais alterar.
                                dInput2::input("name='renasem'", $clienObj) . "<br /><small>(Opcional)</small>"
                            ?>
                        </div>
                    </div>
                    <div class="row">
                        <span>CPF ou CNPJ:</span>
                        <div>
                            <?= $isUpdate ?
                                dHelper2::formataCpfCnpj($clienObj->v('cpf_cnpj')) :
                                dInput2::input("name='cpf_cnpj'", dHelper2::formataCpfCnpj($clienObj->v('cpf_cnpj')));
                            ?></div>
                    </div>
                    <div class="row">
                        <span>RG ou Inscr. Estadual:</span>
                        <div><?= dInput2::input("name='rg_ie'", $clienObj); ?></div>
                    </div>
                    <div class="row">
                        <span>Data de Nascimento:</span>
                        <div style='white-space: nowrap'>
                            <?= dInput2::input("name='data_nasc' style='width: calc(100% - 50px)'", $clienObj,
                                'date'); ?>
                        </div>
                    </div>
                    <div class="row">
                        <span>Estado Civil:</span>
                        <div><?= dInput2::select("name='estado_civil'", "Solteiro,Casado,Separado,Divorciado,Viúvo",
                                $clienObj, false, '-- Selecione --'); ?></div>
                    </div>
                    <div class="row">
                        <span>Estado:</span>
                        <div><?= dInput2::select("name='uf'", dHelper2::getUfList(), $clienObj, false,
                                "Selecione"); ?></div>
                    </div>
                    <div class="row">
                        <span>Cidade:</span>
                        <div><?= dInput2::input("name='cidade'", $clienObj); ?></div>
                    </div>
                    <div class="row">
                        <span>Bairro:</span>
                        <div><?= dInput2::input("name='bairro'", $clienObj); ?></div>
                    </div>
                    <div class="row">
                        <span>Endereço:</span>
                        <div><?= dInput2::input("name='endereco'", $clienObj); ?></div>
                    </div>
                    <div class="row">
                        <span>Dados Bancários<br/><small>(Opcional)</small></span>
                        <div><?= dInput2::textarea("name='dados_bancarios' style='width: 100%' rows='4' placeholder='Banco: \nAgência: \nConta Corrente:'",
                                $clienObj); ?></div>
                    </div>
                    <button>CONFIRMAR</button>
                </div>
            </form>
        <? endif ?>
    </div>

<?php
layBaixo();