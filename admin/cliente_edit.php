<?php
require_once "config.php";
require_once "template.php";

$succMsg = array();
$a = cUsuario::loadOrNew(@$_GET['id']);

if ($_POST) {
    // Vamos processar o checkbox antes do loadArray para evitar
    // o conflito com agente_pending.
    $a->v('agente_pending',
        isset($_POST['agente_pending']) ? '1' : '0'); // Se vier como <select...>, será sobrescrito em loadArray.
    $a->v('agente_captador', isset($_POST['agente_captador']) ? '1' : '0');
    $a->v('agente_vendedor', isset($_POST['agente_vendedor']) ? '1' : '0');

    $a->loadArray($_POST, 'id');

    $selRegioes = (@$_POST['interesse_regioes']) ? implode(";", $_POST['interesse_regioes']) : false;
    $selCulturas = (@$_POST['interesse_culturas']) ? implode(";", $_POST['interesse_culturas']) : false;
    $a->setValue('interesse_regioes', $selRegioes);
    $a->setValue('interesse_culturas', $selCulturas);
    if ($newid = $a->save()) {
        $succMsg[] = "O usuário foi salvo com sucesso!";
    }
}

$dropAgentes = $db->singleQuery("select id,nome from c_usuarios where agente_captador='1' or agente_vendedor='1' " . ($a->v('agente_id') ? " or agente_id='{$a->v('agente_id')}'" : "") . " order by nome");

dAL::layTop(array('bodyTitle' => (!$a->getPrimaryValue() ? 'Novo usuário' : "Usuário: {$a->v('nome')}")));
dAL::goBack(true,
    ($a->isLoaded() ? "<a href='usuario_edit.php'>Cadastrar outro</a> | " : false) .
    ($a->isLoaded() ? "<a href='usuario_audit.php?clien_id={$a->v('id')}'>Auditoria</a>" : false)
);

dAL::boxes($a->listErrors(true), $succMsg);

dALForm::Start();

if ($a->v('agente_pending')) {
    // * Estes mesmos campos estão abaixo de "Renasem"
    dALCampo::Start("Agente");
    dALCampo::Misc(false, "Usuário está aguardando liberação para ser um agente.");
    dALCampo::Misc("Captador:", dInput2::checkbox("name='agente_captador'", $a, " Liberar interface Captador"));
    dALCampo::Misc("Vendedor:", dInput2::checkbox("name='agente_vendedor'", $a, " Liberar interface Vendedor"));
    dALCampo::Misc("Aprovação:",
        dInput2::select("name='agente_pending'", '1=Pendente (bloquear acesso),0=Aprovado (acesso liberado)', $a));
    dALCampo::Finish();
    echo "<br />";
}

dALCampo::Start("Dados cadastrais:");
dALCampo::Misc(
    "Data de Cadastro:",
    $a->v('data_cadastro') . " <span style='color: #00F; font-size: 11px'> | <i>Último acesso:</i> " .
    ($a->v('data_lastlogin') ? $a->v('data_lastlogin') : "<i>Nunca</i>") . "</small>"
);
dALCampo::Text('Razão Social ou Nome:', 'nome');
dALCampo::Text('E-mail', 'email');
dALCampo::Text('Renasem', 'renasem');

if (!$a->v('agente_pending')) {
    dALCampo::Misc("Interface Agente:",
        dInput2::checkbox("name='agente_captador'", $a, " Captador") . " " .
        dInput2::checkbox("name='agente_vendedor'", $a, " Vendedor") . " " .
        dInput2::checkbox("name='agente_pending'", $a, " Aprovação pendente <i>(Bloquear acesso)</i>") . " "
    );
}
if (!$a->isAgente()) {
    dALCampo::Misc("Agente Responsável:",
        dInput2::select("name='agente_id'", $dropAgentes, $a, false, "Nenhum agente (usuário autônomo)")
    );
}

dALCampo::Misc("Tipo", dInput2::select("name='tipo'", 'pf=Pessoa Física,pj=Pessoa Jurídica', $a));
dALCampo::Misc("Avaliação", dInput2::select("name='avaliacao'", [
    '1' => "1 Estrelas. ⭐",
    '2' => "2 Estrelas. ⭐ ⭐",
    '3' => "3 Estrelas. ⭐ ⭐ ⭐",
    '4' => "4 Estrelas. ⭐ ⭐ ⭐ ⭐",
    '5' => "5 Estrelas. ⭐ ⭐ ⭐ ⭐ ⭐",
], $a, false, "-- Automático --"));
dALCampo::Text('Responsavel nome', 'responsavel_nome', false, false,
    " (Quem está operando o sistema em nome deste usuário ou empresa?)");
dALCampo::Misc('CPF/CNPJ', dInput2::input("name='cpf_cnpj' size='13'", $a, 'cpf_cnpj'));
dALCampo::Text('RG/IE', 'rg_ie', 13, false, ' (Opcional)');
dALCampo::Text('Nascimento', 'data_nasc', 'date');
dALCampo::Text('Estado Civil', 'estado_civil', 20);
dALCampo::Text('RG/IE', 'rg_ie', 13, false, ' (Opcional)');
dALCampo::Text('Atividade', 'atividade');
dALCampo::Area("Dados bancários", 'dados_bancarios', 5,
    "Banco, Agência, Conta Corrente, Responsavél pela Assinatura de Compra e Conta Bancária");
dALCampo::Misc("Interesses",
    "Para gerenciar os interesses, acesse <a href='../app/meus-interesses.php' target='_blank' onclick=\"alert('Não esqueça de simular login com o usuário desejado');\">Minha Conta - Meus Interesse</a> na conta do cliente.");
dALCampo::Finish();

echo "<br />";

dALCampo::Start("Endereço:");
dALCampo::Misc('Cep', dInput2::input("name='cep'", $a, 'cep'));
dALCampo::Misc('Cidade/UF',
    dInput2::input("name='cidade'", $a) . " " .
    dInput2::select("name='uf'", dHelper2::getUfList(), $a, false, '-- Selecione --')
);
dALCampo::Misc('Endereco',
    dInput2::input("name='endereco' placeholder='Rua xxxxxxxxxx'", $a) . ", Número " .
    dInput2::input("name='numero'   placeholder='xxx' size='3'")
);
dALCampo::Text('Complemento', 'complemento');
dALCampo::Text('Bairro', 'bairro');
dALCampo::Text('Referencia', 'referencia');
dALCampo::Text('Telefone', 'fone1', 12);
dALCampo::Text('Telefone', 'fone2', 12);


// dALCampo::Text('Facebook id'     , 'facebook_id'     );
// dALCampo::Text('Googleacc id'    , 'googleacc_id'    );
// dALCampo::Text('Disabled'        , 'disabled'        );
dALCampo::Finish();

echo "<br />";

dALCampo::Start("Informações Internas:");
dALCampo::Area("Observações Internas", 'observacoes_internas', 4);
dALCampo::Text('Senha', 'senha', 12);
dALCampo::Finish();

dALForm::Finish();

dAL::layBottom();

