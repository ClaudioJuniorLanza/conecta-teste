<?php
require_once "config.php";
require_once "template.php";

$succMsg = array();
$a = cAnuncio::loadOrNew(@$_GET['id'], 'usuarObj');

if (@$_GET['delAnexo']) {
    $anexoObj = dAnexo::load($_GET['delAnexo']);
    if ($anexoObj && $anexoObj->v('rel') == 'cAnuncio' && $anexoObj->v('rel_id') == $a->v('id')) {
        $anexoObj->delete();
    }
}
if (@$_FILES['addAnexo']['tmp_name']) {
    // Enviando anexo.
    $anexoObj = new dAnexo;
    $anexoObj->v('rel', 'cAnuncio');
    $anexoObj->v('rel_id', $a->v('id'));
    $anexoObj->v('data_add', date('d/m/Y H:i:s'));
    $anexoObj->v('data_mod', date('d/m/Y H:i:s'));
    $anexoObj->save();
    if (!$anexoObj->setFile($_FILES['addAnexo']['tmp_name'], $_FILES['addAnexo']['name'])) {
        $anexoObj->delete();
    }
}
if ($_POST) {
    // Atualizando anúncio
    $a->loadFromArray($_POST, 'id');

    if ($_POST['valor_por_tipo'] == 'embalagem') {
        $a->setValue('valor_por_embalagem', $_POST['valor_por']);
        $a->setValue('valor_por_kg', false);
    } else {
        $a->setValue('valor_por_embalagem', false);
        $a->setValue('valor_por_kg', $_POST['valor_por']);
    }

    if ($newid = $a->save()) {
        $succMsg[] = "O anúncio foi salvo com sucesso!";
    }
}

dAL::layTop(array('bodyTitle' => "Editar anúncio <span style='color: #00F'>#{$a->v('codigo')}</span>"));
dAL::goBack(true, "<a href='../app/ver-anuncio.php?codigo={$a->v('codigo')}' target='_blank'>Ver no sistema</a>");

dAL::boxes($a->listErrors(true), $succMsg);

$acoes = [];
if ($a->v('status') == 'Em Análise') {
    $acoes[] = "<button onclick='aceitarAnuncio(); return false;'>Publicar anúncio!</button>";
    $acoes[] = "<button onclick='rejeitarAnuncio(); return false;'>Rejeitar anúncio</button>";
} else {
    if ($a->v('status') == 'Ag. Propostas') {
        $acoes[] = "<button onclick='encerrarAnuncio(); return false;'>Encerrar agora</button>";
        $acoes[] = "<button onclick='rejeitarAnuncio(); return false;'>Rejeitar anúncio</button>";
    } else {
        if ($a->v('status') == 'Concluído' || $a->v('status') == 'Cancelado') {
            $acoes[] = "<button onclick='reativarAnuncio(); return false;'>Reativar anúncio<br /><small>Não recomendado</small></button>";
        }
    }
}

$statusStr = $a->v('status');
if ($statusStr == 'Em Análise') {
    $statusStr = "<b style='color: #F00'>Em Análise</b>";
} elseif ($statusStr == 'Ag. Propostas') {
    $statusStr = "<b style='color: #008'>Publicado</b> - Aguardando propostas.";
} elseif ($statusStr == 'Concluído') {
    $statusStr = "<b style='color: #080'>Concluído</b> - Anúncio já arquivado.";
} else {
    $statusStr = "<b style='color: #777'>Cancelado</b><br /><small>{$a->v('encerrado_motivo')}</small>";
}

dALCampo::Start("Gerenciar anúncio");
dALCampo::Misc("Usuário:<br /><small>{$a->v('usuarObj')->v('renasem')}</small>",
    $a->v('usuarObj')->v('nome') . "<br /><small>Fone: {$a->v('usuarObj')->v('fone1')} | <a href='mailto:{$a->v('usuarObj')->v('email')}'>{$a->v('usuarObj')->v('email')}</a></small>");
dALCampo::Read('Criado em:', 'data_anuncio');
dALCampo::Read("Publicado em:", 'data_ini_cotacao');
// dALCampo::Misc('Ativo até:',    'data_encerrado', 'date', false, " (Encerrar automaticamente nesta data)");
dALCampo::Misc("Status:", $statusStr);
dALCampo::Misc("Ações:", implode(" ", $acoes));
dALCampo::Finish(true);

$exPropostas = cProposta::multiLoad("where anunc_id='{$a->v('id')}' and !ISNULL(status) and status != 'Sem Interesse'",
    'usuarObj');
echo "<div style='padding: 16px; border: 1px solid #CCC; border-top: 0' id='listaExPropostas'>";
if ($exPropostas) {
    // 'Sem Interesse','Enviada','Rejeitada','Rejeitada pelo Admin','Aceita','Negócio Fechado','Negócio Desfeito','Cancelada'
    $_options = [
        'Enviada' => "Ag. Anunciante",
        'Rejeitada pelo Admin' => 'Rejeitada pelo Admin',
        'Rejeitada' => 'Rejeitada',
        'Aceita' => "Ag. Intermediação",
        'Negócio Fechado' => 'Negócio Fechado',
        'Negócio Desfeito' => 'Negócio Desfeito',
        'Cancelada' => 'Cancelada',
    ];

    echo "  <b>Todas as propostas recebidas:</b><br />";
    echo "  <table width='100%' cellpadding='6' cellspacing='0' border='1' style='border-collapse: collapse; margin-top: 8px'>";
    echo "      <tr>";
    echo "          <td><b>Data</b></td>";
    echo "          <td><b>Cliente</b></td>";
    echo "          <td><b>Anexos</b></td>";
    echo "          <td><b>Valor</b></td>";
    echo "          <td><b>Região</b></td>";
    echo "          <td><b>Justificativa</b></td>";
    echo "          <td align='center'><b>Status</b></td>";
    echo "      </tr>";
    foreach ($exPropostas as $propoObj) {
        $_usuarObj = $propoObj->v('usuarObj');
        echo "      <tr class='proposta' rel='{$propoObj->v('id')}' " . ($propoObj->v('data_aceite') ? "data-notifyaceite='yes'" : "") . ">";
        echo "          <td>" . substr($propoObj->v('data_proposta'), 0, 16) . "</td>";
        echo "          <td><a href='cliente_edit.php?id={$_usuarObj->v('id')}' target='_blank'>{$_usuarObj->v('nome')}</a></td>";
        echo "          <td align='center'><a href='ifr.anunc_edit.php?action=verAnexos&propoId={$propoObj->v('id')}' style='text-decoration: none' class='openAnexos'><i class='fa fa-paperclip'></i> <small>Anexos</small></a></td>";
        echo "          <td>R$ " . dHelper2::moeda($propoObj->v('valor')) . "</td>";
        echo "          <td>{$propoObj->v('regiao')}</td>";
        echo "          <td>" . nl2br(htmlspecialchars($propoObj->v('justificativa'))) . "</td>";
        echo "          <td align='center'>";
        if (!$propoObj->v('data_revisado')) {
            echo "<div class='revisaoOptions'>";
            echo "  <small style='display: block; margin-bottom: 4px'>Ag. Revisão</small>";
            echo "	<button style='background: #9F9; border: 1px solid #090' class='btnEncaminhar''>Encaminhar</button> ";
            echo "	<button style='background: #F99; border: 1px solid #900' class='btnRejeitar''>Rejeitar</button>";
            echo "</div>";
            echo "<span style='display: none'>";
        }
        echo dInput2::select("name='status'", $_options, $propoObj);
        echo " <a href='#' class='btnRejeitar fa fa-times' style='color: #F00; text-decoration: none'></a>";
        if (!$propoObj->v('data_revisado')) {
            echo "</span>";
        }
        echo "          </td>";
        echo "      </tr>";
    }
    echo "  </table>";
    echo "  <br />";
} else {
    echo "<b>Ainda ninguém fez uma proposta para este anúncio.</b><br />";
}
echo "</div>";


echo "<div id='debug'></div>";
echo "<br />";

dALForm::Start();
dALCampo::Start("Anúncio de <b>{$a->v('negocio')}</b>");
dALCampo::Misc('Variedade/Cultivar:',
    "<b>{$a->v('varieObj')->v('variedade')}</b><br />" .
    "<small>Cultura: {$a->v('varieObj')->v('cultura')} | Tecnologia: {$a->v('varieObj')->v('tecnologia')}</small>"
);
dALCampo::Misc('Categoria', dInput2::selectStr("name='categoria'", dHelper2::csDropCategoria(), $a));
dALCampo::Misc('Germinacao', dInput2::selectStr("name='germinacao'", dHelper2::csDropGerminacao(), $a));
dALCampo::Misc('Embalagem', dInput2::selectStr("name='embalagem'", dHelper2::csDropEmbalagem(), $a));
dALCampo::Misc('Vigor ea48h', dInput2::selectStr("name='vigor_ea48h'", dHelper2::csDropVigorEA48h(), $a));
dALCampo::Misc('Peneira', dInput2::selectStr("name='peneira'", dHelper2::csDropPeneira(), $a));
dALCampo::Misc('Tratam indust', dInput2::selectStr("name='tratam_indust'", dHelper2::csDropTratamentoIndustrial(), $a));
dALCampo::Text('Tratamento', 'tratam_texto', 30);
dALCampo::Misc('Pms', dInput2::selectStr("name='pms'", dHelper2::csDropPMS(), $a));
dALCampo::Text('Quantidade', 'quantidade');
dALCampo::Misc('Frete', dInput2::selectStr("name='frete'", dHelper2::csDropFrete(), $a));
dALCampo::Misc("Valor desejado: ",
    dInput2::input("name='valor_por' size='4'",
        $a->v('valor_por_kg') ? $a->v('valor_por_kg') : $a->v('valor_por_embalagem')) .
    " por " . dInput2::select("name='valor_por_tipo'", "embalagem,kg=kilograma",
        $a->v('valor_por_kg') ? "kg" : "embalagem")
);
dALCampo::Text('Valor por embalagem', 'valor_por_embalagem', 8);
dALCampo::Misc("Cidade/UF",
    dInput2::input("name='cidade'", $a) . " / " . dInput2::input("name='uf' maxlength='2' style='width: 50px'", $a));
dALCampo::Misc('<i>ou</i> Regiao',
    dInput2::selectStr("name='regiao'", dHelper2::csDropRegiao(), $a, false, '-- Não informado --'));
dALCampo::Misc('Forma pgto', dInput2::selectStr("name='forma_pgto'", dHelper2::csDropFormaPgto(), $a));
dALCampo::Finish();

dALForm::Finish();

?>
    <script>
        var id = "<?=$a->v('id')?>";

        function _quickActionAndReload(params) {
            $.post("ajax.anunc_edit.php", params, function (ret) {
                if (ret == 'OK') {
                    location.href = 'anunc_edit.php?id=' + id;
                } else {
                    $("#debug").html(ret);
                }
            })
        }

        function aceitarAnuncio() {
            _quickActionAndReload({id: id, action: 'aceitarAnuncio'});
        }

        function rejeitarAnuncio() {
            var _motivo = prompt("Informe o motivo da rejeição.\nSe você informar, o usuário será notificado.\nSe deixar em branco, ele não será notificado.", "");
            if (_motivo === null || _motivo === false || _motivo === undefined) {
                return false;
            }

            _quickActionAndReload({id: id, action: 'rejeitarAnuncio', motivo: _motivo});
        }

        function encerrarAnuncio() {
            _quickActionAndReload({id: id, action: 'encerrarAnuncio'});
        }

        function reativarAnuncio() {
            if (confirm("Utilize esta opção apenas se você encerrou um anúncio por engano. Não reative anúncios antigos: Crie novos.")) {
                _quickActionAndReload({id: id, action: 'reativarAnuncio'});
            }
        }

        $(function () {
            var jqoStatus = $("#listaExPropostas select[name=status]");
            var _refreshColors = function () {
                jqoStatus.each(function () {
                    var status = $(this).val();
                    var jqoRow = $(this).closest('.proposta');
                    var isApproved = !($(".revisaoOptions:visible", jqoRow).length);

                    if (status == "Rejeitada pelo Admin" || status == "Cancelada") {
                        $(".fa-times", jqoRow).hide();
                    } else {
                        $(".fa-times", jqoRow).show();
                    }

                    if (!isApproved || status == 'Aceita') {
                        // Ação exigida por parte do administrador, fica em vermelho.
                        // Ou exige aprovação, ou está aguardando intermediação.
                        jqoRow.css('background', '#9bffd8');
                        return;
                    }

                    // 'Enviada','Rejeitada','Rejeitada pelo Admin','Aceita','Negócio Fechado','Negócio Desfeito','Cancelada'
                    if (status == 'Enviada') {
                        // Aguardando decisão do anunciante.
                        jqoRow.css('background', '#FFF');

                    }
                    if (status == 'Negócio Fechado') {
                        jqoRow.css('background', '#9F9');
                    }
                    if (status == 'Negócio Desfeito') {
                        jqoRow.css('background', '#CCC');
                    }
                    if (status == 'Rejeitada pelo Admin') {
                        jqoRow.css('background', '#DDD');
                    }
                    if (status == 'Rejeitada') {
                        jqoRow.css('background', '#DDD');
                    }
                    if (status == 'Cancelada') {
                        jqoRow.css('background', '#CCC');
                    }
                });
            };
            _refreshColors();

            $(".btnEncaminhar").click(function () {
                var notify = confirm("Deseja também enviar e-mail para o anunciante, dizendo que há uma nova proposta?");

                var _waitEl = $.dEip.showLoading();
                var jqoBtn = $(this);
                var jqoOptions = $(this).closest(".revisaoOptions");
                var jqoSelect = jqoOptions.next();
                $('button', jqoOptions).prop('disabled', true);
                $(this).html("<i class='fa fa-spinner fa-spin'></i> Aguarde...");

                $.post("ajax.anunc_edit.php", {
                    id: id,
                    propoId: $(this).closest('.proposta').attr('rel'),
                    action: 'propoEncaminhar',
                    notify: notify ? '1' : '0',
                }, function (ret) {
                    $.dEip.endLoading(_waitEl, ret);
                    if (ret == 'OK') {
                        jqoOptions.fadeOut(function () {
                            _refreshColors();
                            jqoSelect.fadeIn();
                        });
                    } else {
                        $('button', jqoOptions).prop('disabled', false);
                        jqoBtn.html("Encaminhar");
                    }
                });

                return false;
            });
            $(".btnRejeitar").click(function () {
                var motivo = prompt("Você está recusando essa proposta.\nDeseja explicar o motivo para o proponente?.\n\nSe você deixar em branco, o proponente não será notificado.");
                if (motivo === false || motivo === null || motivo === undefined) {
                    return false;
                }

                var allowRedo = confirm("O proponente poderá fazer outra proposta neste anúncio?");

                var _waitEl = $.dEip.showLoading();
                var jqoRow = $(this).closest('.proposta');
                var jqoBtn = $(this);
                var jqoOptions = $(this).closest(".revisaoOptions");
                var jqoDiv = jqoOptions.next();
                var jqoSelect = $("select", jqoDiv);
                $('button', jqoOptions).prop('disabled', true);

                if (jqoBtn.is("button")) {
                    jqoBtn.html("<i class='fa fa-spinner fa-spin'></i> Aguarde...");
                }

                $.post("ajax.anunc_edit.php", {
                    id: id,
                    propoId: $(this).closest('.proposta').attr('rel'),
                    action: 'propoRejeitar',
                    motivo: motivo,
                    allowRedo: allowRedo ? '1' : '0',
                }, function (ret) {
                    $.dEip.endLoading(_waitEl, ret);
                    if (ret == 'OK') {
                        if (allowRedo) {
                            jqoRow.fadeOut(function () {
                                jqoRow.remove();
                            });
                        } else {
                            jqoOptions.fadeOut(function () {
                                _refreshColors();
                                jqoSelect.val('Rejeitada pelo Admin');
                                jqoDiv.fadeIn();
                            });
                        }
                    } else {
                        $('button', jqoOptions).prop('disabled', false);
                        if (jqoBtn.is("button")) {
                            jqoBtn.html("Rejeitar");
                        }
                    }
                });

                return false;
            });

            jqoStatus.change(function () {
                // Alteração do status.
                var jqoSelect = $(this);
                var newStatus = $(this).val();
                if (newStatus == 'Aceita') {
                    var anuncianteFoiNotificado = $(this).closest('.proposta').data('notifyaceite');
                    if (!anuncianteFoiNotificado) {
                        if (!confirm("O proponente será notificado do ACEITE dessa proposta...\nTem certeza??")) {
                            return false;
                        }

                        $(this).closest('.proposta').data('notifyaceite', 'yes');
                    }
                }

                var _waitEl = $.dEip.showLoading();
                $.post("ajax.anunc_edit.php", {
                    id: id,
                    propoId: $(this).closest('.proposta').attr('rel'),
                    action: 'propoChangeStatus',
                    newStatus: newStatus,
                }, function (ret) {
                    if (ret.substr(0, 3) == "OK=") {
                        $.dEip.endLoading(_waitEl, "OK");
                    } else {
                        $.dEip.endLoading(_waitEl, ret);
                    }

                    _refreshColors();
                });
                return false;
            });

        });


        // Etapa 3: Propostas aguardando o ACEITE do cliente.
        // Etapa 4: Propostas aguardando a intermediação do administrador.
        // Etapa 5: Propostas aguardando a conclusão.
        $(function () {
            return false;
            var _isLoading = false;
            var jqoStatus = $("#listaExPropostas select[name=status]");

            jqoStatus.each(function () {
                $(this).data('oldValue', $(this).val());
            });
            jqoStatus.change(function () {
                if (_isLoading) {
                    return false;
                }

                var jqoSelect = $(this);
                var jqoRow = jqoSelect.closest('tr');
                var propoId = $(this).closest('.proposta').attr('rel');
                var _waitEl = $.dEip.showLoading();
                var newValue = jqoSelect.val();
                _isLoading = true;
                jqoSelect.prop('disabled', true);

                $.post("ajax.anunc_edit.php", {
                    id: id,
                    propoId: propoId,
                    action: 'changeStatus',
                    newStatus: $(this).val(),
                }, function (ret) {
                    $.dEip.endLoading(_waitEl, ret);
                    _isLoading = false;
                    jqoSelect.prop('disabled', false);
                    if (ret == 'OK') {
                        jqoSelect.data('oldValue', newValue);
                        _refreshColors();
                    } else {
                        jqoSelect.val(jqoSelect.data('oldValue'));
                    }
                })
            });

            var jqoConcBtn = $("#btnSetConcluido");
            var _setConcluido = function () {
                if (_isLoading) {
                    return false;
                }

                var _canProceed = true;
                jqoStatus.each(function () {
                    var _v = $(this).val();
                    if (_v != 'Aceita' && _v != 'Rejeitada') {
                        _canProceed = false;
                    }
                });

                if (!_canProceed) {
                    alert("Todas as propostas ativas devem ser aceitas ou rejeitadas, antes de prosseguir.");
                    return false;
                }

                var _oHtml = jqoConcBtn.html();
                _isLoading = true;
                jqoConcBtn.html("<i class='fa fa-spinner fa-spin'></i> Aguarde...");
                jqoStatus.prop('disabled', true);
                $.post("ajax.anunc_edit.php", {
                    id: id,
                    action: (jqoConcBtn.attr('rel') == 'aceite') ? 'setAceiteFromAdmin' : 'setConcluido',
                }, function (ret) {
                    $("#debug").html(ret);
                    if (ret == 'OK') {
                        location.href = 'anunc_edit.php?id=' + id;
                        return;
                    } else {
                        alert(ret);
                        _isLoading = false;
                        jqoConcBtn.html(_oHtml);
                        jqoStatus.prop('disabled', false);
                    }
                });

            };
            jqoConcBtn.click(_setConcluido);
        });

        function rejectAnuncio(wasMadePublic) {
            var askStr = wasMadePublic ?
                "Este anúncio já foi visto por algumas pessoas. Deseja cancelá-lo mesmo assim?" :
                "Você vai rejeitar este anúncio. Tem certeza?\nEssa operação é irreversível.";

            if (!confirm(askStr)) {
                return false;
            }

            var motivo = $.trim(prompt("Informe o motivo. Esse motivo ficará disponível apenas para o anunciante..", "<?=addslashes($a->v('encerrado_motivo'))?>"));
            if (!motivo || !motivo.length) {
                alert("Você precisa informar um motivo para a rejeição.");
                return false;
            }

            $.post("ajax.anunc_edit.php", {
                id: id,
                action: 'reject',
                motivo: motivo,
            }, function (ret) {
                if (ret == 'OK') {
                    location.reload();
                } else {
                    $("#debug").html(ret);
                }
            }).fail(function () {
                $("#debug").html("Falha na rede.");
            });
        }

        // Faz o botão "Anexos" usar o Fancybox.
        $(function () {
            $(".openAnexos").fancybox({
                type: 'iframe',
            });
        });
    </script>
<?php
dAL::layBottom();

