<?php
require_once "config.php";

if (!isset($includeContext)) {
    die("Esta página não pode ser chamada diretamente.");
}

/**
 * @var array $params ;
 * @var cAnuncio $anuncObj ;
 * @var cUsuario $usuarObj ;
 * @var cProposta $myPropoObj ;
 */
$anuncObj = $params['anuncObj'];
$usuarObj = $params['usuarObj']; // Usuário que está *vendo* a tela.
$expandPropostas = $params['settings']['expandPropostas'];
$isTroca = ($anuncObj->v('negocio') == 'Troca');
$isAnunciante = ($usuarObj->v('id') == $anuncObj->v('usuarObj')->v('id'));
$myPropoObj = $isAnunciante ? false : $anuncObj->getPropoObj($usuarObj);

// Pode fazer oferta?
// --> Escreverá o div.makeOffer e div.offerReceived
// --> Liberará a opção "Não me interessa"
// --> Colocará o "data-canMakeOffer" na row.
$canMakeOffer = false;
if (!$isAnunciante) {
    // Não é o autor, talvez possa fazer a oferta.
    if ($anuncObj->v('status') == 'Ag. Propostas') {
        // É um anúncio que está em aberto. Talvez possa fazer a oferta.
        if (!$myPropoObj || !$myPropoObj->v('status') || $myPropoObj->v('status') == 'Sem Interesse') {
            // Ainda não fez proposta. Pode fazer.
            $canMakeOffer = true;
        }
    }
}

// Exibir "Minha oferta"
$showMyOffer = false;
if ($myPropoObj && $myPropoObj->v('status') && $myPropoObj->v('status') != 'Sem Interesse') {
    // Enviada | Rejeitada | Rejeitada pelo Admin | Aceita | Negócio Fechado | Negócio Desfeito | Cancelada
    // lightgreen, lightgray, green, red, yellow
    $showMyOffer = true;
}

// Exibir "Ofertas recebidas"
$showOfferReceived = false;
$offerReceivedList = [];
if ($isAnunciante) {
    $offerReceivedList = cProposta::multiLoad("where anunc_id='{$anuncObj->v('id')}' and !isnull(status) and status NOT IN('Sem Interesse', 'Rejeitada pelo Admin') and !isnull(data_revisado)");
    $showOfferReceived = sizeof($offerReceivedList);
}

// Toggles e strs preparadas para exibição
$showBtnRemove = ($isAnunciante && in_array($anuncObj->v('status'), ['Em Análise', 'Ag. Propostas']));
$showBtnRenew = ($isAnunciante && $anuncObj->v('status') == 'Concluído');
$showBtnNotInterested = (!$isAnunciante && $canMakeOffer && (!$myPropoObj || $myPropoObj->v('status') != 'Sem Interesse'));
$showNew = (!$isAnunciante && !$myPropoObj);
$showStars = (!$isAnunciante);
$strCulturaFull = $anuncObj->v('varieObj')->v('cultura');         // Ex: Milho-Safrinha, Feijão-bla-bla--la
$strCultura = dHelper2::csGetCulturaSimples($strCulturaFull); // Ex: Soja, Milho, Feijao. etc.
$showTipoNegocio = $isAnunciante ?
    (substr($anuncObj->v('negocio'), 0, -1) . "o") : // Compro, Vendo, Troco
    (($anuncObj->v('negocio') == 'Venda') ? "Vende" : $anuncObj->v('negocio')) . "-se"; // Compra-se, Vende-se, Troca-se

$strRegiao = ($anuncObj->v('negocio') == 'Venda' || $isTroca) ?
    ['Origem da semente', 'Destino da semente'] : // Anúncio x Oferta
    ['Destino da semente', 'Origem da semente']; // Anúncio x Oferta

$strComprador = ($anuncObj->v('negocio') == 'Venda') ?
    ['vendedor', 'comprador'] :                   // Anuncio x Oferta
    ['comprador', 'vendedor'];                   // Anuncio x Oferta
?>
<div class="anuncRow" rel="<?= $anuncObj->v('codigo') ?>"
    <?= $canMakeOffer ? " data-canMakeOffer='yes'" : "" ?>
     data-negocio="<?= $anuncObj->v('negocio') ?>"
     data-cultura="<?= $strCultura ?>"
     data-variedade="<?= $anuncObj->v('varieObj')->v('id') ?>"
     data-embalagem="<?= $anuncObj->v('embalagem') ?>"
     data-regiao="<?= $anuncObj->v('regiao') ?>"
>
    <div class="fotoAndDetails">
        <?= dUsuario::isLogged() ? "<a href='../admin/anunc_edit.php?id={$anuncObj->v('id')}' target='_blank' title='Abrir no painel administrativo'>" : "" ?>
        <div class="foto" title="<?= htmlspecialchars($strCulturaFull) ?>"
             style="background-image: url(images/sementes/<?= dHelper2::stringToUrl($strCultura) ?>.jpg);">
            <? if ($showNew): ?>
                <div class="new">Novo!</div>
            <? endif ?>
            <div class="sub"><?= mb_strtoupper($strCultura) ?></div>
        </div>
        <?= dUsuario::isLogged() ? "</a>" : "" ?>
        <div class="details">
            <div class="rightSide">
                <?php if ($showStars): ?>
                    <span class="stars"
						<?php if (dUsuario::isLogged()): ?>
                            title="<?=
                            "Clique para simular login como:\n" .
                            htmlspecialchars("{$anuncObj->v('usuarObj')->v('nome')}\n{$anuncObj->v('usuarObj')->v('renasem')}")
                            ?>"
                            onclick="if(confirm(this.title)) $('#admDropUsers .list a[rel=<?= $anuncObj->v('usuar_id') ?>]').click();"
                            onmouseover="$(this).css('background', '#FFC')"
                            onmouseout="$(this).css('background', '')"
                        <?php endif ?>
					>
						<small>Qualificação do <?= $isTroca ? "anunciante" : $strComprador[0] ?>:</small>
						<?= str_repeat("<i class='fa fa-star'></i> ", $anuncObj->v('usuarObj')->getAvaliacao()) ?>
					</span>
                <?php endif ?>
            </div>
            <div class="variedade">
                <i><?= $showTipoNegocio ?>: <b><?= $strCulturaFull ?></b></i><br/>
                <?= $anuncObj->v('varieObj')->v('variedade') ?>
            </div>
            <div class="line1">
                <span class='caract'><i><?= $isTroca ? "Região" : $strRegiao[0] ?>:</i> <b><?= $anuncObj->v('cidade') ? "{$anuncObj->v('cidade')}, {$anuncObj->v('uf')}" : $anuncObj->v('regiao') ?></b></span>
                <span class='caract'><i>Tecnologia:</i> <b><?= $anuncObj->v('varieObj')->v('tecnologia') ?></b></span>
            </div>
            <div class="line2">
                <span class='caract'><i>Embalagem:</i> <b><?= $anuncObj->v('embalagem') ?></b></span>
                <span class='caract'><i>Quantidade:</i>
					<b>
						<?= number_format($anuncObj->v('quantidade'), 0, ',', '.') ?>
						<small><?= $anuncObj->v('valor_por_kg') ? "kg" : "un" ?></small>
					</b>
				</span>
            </div>
        </div>
    </div>
    <div class="moreAbout">
        <div class="expand" style="display: none">
            <div class="maisDados">
                <b>Ficha Técnica</b>
                <?php if ($anuncObj->v('categoria')): ?>
                    <span class='caract'><i>Categoria:</i> <b><?= $anuncObj->v('categoria') ?></b></span>
                <?php endif ?>
                <?php if ($anuncObj->v('germinacao')): ?>
                    <span class='caract'><i>Germinação:</i> <b><?= $anuncObj->v('germinacao') ?></b></span>
                <?php endif ?>
                <?php if ($anuncObj->v('vigor_ea48h')): ?>
                    <span class='caract'><i>Vigor E.A. 48h:</i> <b><?= $anuncObj->v('vigor_ea48h') ?></b></span>
                <?php endif ?>
                <?php if ($anuncObj->v('peneira')): ?>
                    <span class='caract'><i>Peneira:</i> <b><?= $anuncObj->v('peneira') ?></b></span>
                <?php endif ?>
                <?php if ($anuncObj->v('pms')): ?>
                    <span class='caract'><i>PMS:</i> <b><?= $anuncObj->v('pms') ?></b></span>
                <?php endif ?>
                <?php if ($anuncObj->v('tratam_indust')): ?>
                    <span class='caract'><i>Tratamento industrial:</i> <b><?= $anuncObj->v('tratam_indust') ?></b>
						<? if ($anuncObj->v('tratam_texto')): ?>
                            <span><?= htmlspecialchars($anuncObj->v('tratam_texto')) ?></span>
                        <? endif ?>
					</span>
                <?php endif ?>

                <b>Entrega e Pagamento</b>
                <span class='caract'><i>Frete:</i> <b><?= $anuncObj->v('frete') ?></b></span>
                <span class='caract'><i><?= $strRegiao[0] ?>:</i> <b><?= $anuncObj->v('cidade') ? "{$anuncObj->v('cidade')}, {$anuncObj->v('uf')}" : $anuncObj->v('regiao') ?></b></span>
                <span class='caract'><i>Forma de Pagamento:</i> <b><?= $anuncObj->v('forma_pgto') ?></b></span>
                <?php if ($anuncObj->v('valor_royalties')): ?>
                    <span class='caract'><i>Valor dos Royalties:</i> <b><?= dHelper2::moeda($anuncObj->v('valor_royalties')) ?></b></span>
                <?php endif ?>

                <?php if ($isTroca && $anuncObj->v('trocaVarieObj')): ?>
                    <b>O que aceita em troca:</b>
                    <span class='caract'>
						<i>Cultura:</i>
						<b><?= $anuncObj->v('trocaVarieObj')->v('cultura') ?></b>
					</span>
                    <span class='caract'>
						<i>Variedade:</i>
						<b><?= $anuncObj->v('trocaVarieObj')->v('variedade') ?></b>
					</span>
                <?php endif ?>
            </div>
            <?php if ($canMakeOffer): ?>
                <div class="makeOfferGroup">
                    <a href='#' class='btnToggleOffer'>
                        <i class='fa fa-arrow-right'></i> Gostou? <u>Faça uma oferta</u>
                        para <?= $isTroca ? "o anunciante" : "o {$strComprador[0]}" ?>!
                    </a>
                    <div class="introText" style='display: none'>
                        <a href='#' class='btnCloseIntro'>Dispensar ajuda <i class='fa fa-times'></i></a>
                        <b>Como funciona:</b><br/>
                        <?php if (!$isTroca): ?>
                            <li>Informe sua <b>melhor oferta</b>;</li>
                        <?php else: ?>
                            <li>Informe os dados abaixo e veja os termos do contrato;</li>
                        <?php endif ?>
                        <li>Se <?= $isTroca ? "a outra parte" : "o {$strComprador[0]}" ?> aceitar, colocaremos vocês em
                            contato;
                        </li>
                    </div>
                    <form class="offerExpand" style='display: none'>
                        <div class="inpHolders">
                            <?php if (!$isTroca): ?>
                                <div class="inpGrp">
                                    <i>Qual a sua proposta?</i>
                                    R$ <input placeholder="" name='valor' size='6'/><br/>
                                    <div align='center'>
                                        <small>(Por
                                            <b><?= $anuncObj->v('valor_por_kg') ? "kilograma" : $anuncObj->v('embalagem') ?></b>)</small>
                                    </div>
                                </div>
                            <?php endif ?>
                            <div class="inpGrp">
                                <i><?= $strRegiao[1] ?>:</i>
                                <span><?= dInput2::select("name='regiao'", dHelper2::csDropRegiao(),
                                        @$_COOKIE['last-regiao'], false, '-- Selecione --'); ?></span>
                            </div>
                            <div class="inpGrp">
                                <i>
                                    <?= $isTroca ? "Tem alguma observação?" : "Justifique sua oferta" ?>
                                    <small>(Opcional)</small>
                                </i>
                                <span><?= dInput2::textarea("name='justifique'"); ?></span>
                            </div>
                        </div>
                        <div class='terms'>
                            <label>
                                <input type='checkbox' class='sliderSwitch' name='acceptTerms' value='1'>
                                <span>Li e aceito os <a href='download/Contrato-Conecta-Sementes-rev3.pdf'
                                                        target='_blank'>Termos do Contrato</a></span>
                            </label>
                        </div>
                        <div class="offerActions">
                            <a href="#" class='btnConfirmOffer'><i class='fa fa-dollar fa-fw'></i> Confirmar minha
                                oferta! </a>
                            <a href="#" class='btnCloseDetails'><i class="fa fa-times"></i> Agora não</a>
                        </div>
                    </form>
                </div>
            <?php endif ?>
        </div>

        <!-- ACTIONS -->
        <div class="actions">
            <a href="<?= $anuncObj->getLink() ?>" class="btnMaisInformacoes <?= $canMakeOffer ? 'green' : '' ?>">
                <i class='fa fa-fw fa-caret-right'></i>
                <span><?= $canMakeOffer ? " Gostei! Mostre mais..." : "Mais informações" ?></span>
            </a>
            <? if ($showBtnNotInterested): ?>
                <div class="notInterestedGroup">
                    <a href="#" class='btnNotInterested'>Não me interessa</a>
                    <div class="expandHolder">
                        <div class="expandNi" style='display: none'>
                            <b>Não mostrar novamente:</b><br/>
                            <a href="#" rel='this'><b>Apenas este anúncio</b></a>
                            <a href="#" rel='cultura'>Esta cultura (<?= ucfirst($strCultura) ?>)</a>
                            <a href="#" rel='variedade'>Esta variedade</a>
                            <a href="#" rel='embalagem'>Esta embalagem</a>
                            <a href="#" rel='regiao'>Esta região</a>
                            <a href="meus-interesses.php" class='managePrefs'>Gerenciar interesses</a>
                        </div>
                    </div>
                </div>
            <? endif ?>
            <? if ($showBtnRemove): ?>
                <a href="#" class='btnRemoverAnunc'>
                    Encerrar este anúncio
                    <small>Não receber mais propostas</small>
                </a>
            <? endif ?>
            <? if ($showBtnRenew): ?>
                <a href="#" class='btnReactivateAnunc'>
                    Anunciar novamente
                    <small>Criar anúncio idêntico<!-- por +2 semanas --></small>
                </a>
            <? endif ?>
        </div>

        <? if ($canMakeOffer): ?>
            <div class="offerReceived" style='display: none'>
                <b>Parabéns! Sua oferta foi enviada!</b>
                <span>Agora é aguardar a resposta do vendedor.</span>
                <div class='tutorialPropostasMenuLateral'>
                    Para ver suas propostas, acesse <a href='propostas.php?t=enviadas'><b>Minhas Propostas</b></a> no
                    menu lateral.
                </div>
            </div>
        <? endif ?>

        <? if ($showMyOffer): ?>
            <div class="myOffer">
                <b>Você fez uma oferta neste anúncio:</b>
                <div class="myOfferStatus">
					<span class="caract">
						<i>Situação da sua proposta:</i>
						<b class='bg<?= $myPropoObj->getColor() ?>'>
							<? if ($myPropoObj->v('status') == 'Enviada'): ?>
                                Aguardando aceite da outra parte.
                            <? elseif ($myPropoObj->v('status') == 'Aceita'): ?>
                                Aguardando intermediação
                            <? elseif ($myPropoObj->v('status') == 'Rejeitada pelo Admin'): ?>
                                Proposta foi rejeitada
                            <? else: ?>
                                <?= $myPropoObj->v('status') ?>
                            <? endif ?>
						</b>
					</span>
                    <div class="caract">
                        <i>Data da proposta:</i>
                        <b><?= substr($myPropoObj->v('data_proposta'), 0, 10) ?></b>
                    </div>
                    <? if ($myPropoObj->v('valor')): ?>
                        <span class="caract">
							<i>Seu valor:</i>
							<b><?= dHelper2::moeda($myPropoObj->v('valor')); ?></b>
						</span>
                    <? endif ?>
                    <span class="caract">
						<i><?= $isTroca ? 'Região' : $strRegiao[1] ?>:</i>
						<b><?= $myPropoObj->v('regiao') ?></b>
					</span>
                    <? if ($myPropoObj->v('justificativa')): ?>
                        <span class="caract">
							<i>Sua justificativa:</i>
							<b><?= htmlspecialchars($myPropoObj->v('justificativa')) ?></b>
						</span>
                    <? endif ?>
                </div>
            </div>
        <? endif ?>

        <? if ($showOfferReceived): ?>
            <div class="listOffers">
                <b>Você recebeu as seguintes propostas:</b>
                <? foreach ($offerReceivedList as $idx => $propoObj):
                    /** @var cProposta $propoObj */
                    ?>
                    <div class="offerRow" rel="<?= $propoObj->v('id') ?>">
                        <a href="#" class="propoTitle toggleNext shown">
                            <div class="ptLeft">
                                <i class='fa fa-fw fa-caret-right'></i> <b>Proposta #<?= $idx + 1 ?>:</b>
                            </div>
                            <div class="ptRight bg<?= $propoObj->getColor() ?>">
                                <? if ($propoObj->v('status') == 'Enviada'): ?>
                                    Aguardando sua decisão
                                <? elseif ($propoObj->v('status') == 'Aceita'): ?>
                                    Aguardando intermediação
                                <? else: ?>
                                    <?= $propoObj->v('status') ?>
                                <? endif ?>
                            </div>
                        </a>
                        <div class="propoBody">
							<span class='caract'>
								<i>Data da proposta</i>
								<b><?= $propoObj->v('data_proposta') ?></b>
							</span>
                            <div>
								<span class='caract'>
									<i>Avaliação d<?= $isTroca ? "a outra parte" : "o {$strComprador[1]}" ?></i>
									<b class='stars'
										<? if (dUsuario::isLogged()): ?>
                                            title="<?=
                                            "Clique para simular login como:\n" .
                                            htmlspecialchars("{$propoObj->v('usuarObj')->v('nome')}\n{$propoObj->v('usuarObj')->v('renasem')}")
                                            ?>"
                                            onclick="if(confirm(this.title)) $('#admDropUsers .list a[rel=<?= $propoObj->v('usuar_id') ?>]').click();"
                                            onmouseover="$(this).css('background', '#FFC')"
                                            onmouseout="$(this).css('background', '')"
                                        <? endif ?>
									>
										<?= str_repeat("<i class='fa fa-star'></i> ",
                                            $propoObj->v('usuarObj')->getAvaliacao()) ?>
									</b>
								</span>
                            </div>
                            <? if ($propoObj->v('valor')): ?>
                                <span class="caract">
									<i>Valor:</i>
									<b>R$ <?= dHelper2::moeda($propoObj->v('valor')) ?></b>
								</span>
                            <? endif ?>
                            <div class="caract">
                                <i><?= $isTroca ? "Região" : $strRegiao[1] ?>:</i>
                                <b><?= $propoObj->v('regiao') ?></b>
                            </div>
                            <br/>
                            <? if ($propoObj->v('justificativa')): ?>
                                <div class="caract">
                                    <i>Justificativo do preço:</i>
                                    <b><?= htmlspecialchars($propoObj->v('justificativa')) ?></b>
                                </div>
                            <? endif ?>
                            <? if ($propoObj->v('status') == 'Enviada'): ?>
                                <div class='acceptTerms'>
                                    <?= dInput2::checkbox("name='acceptTerms'", false,
                                        " Aceito os <a href='download/Contrato-Conecta-Sementes-rev3.pdf' target='_blank'>termos e condições de uso do sistema</a>."); ?>
                                </div>
                                <div class="propoActions">
                                    <a href="#" class='btnPropoAccept'><i class='fa fa-check'></i> Aceitar proposta</a>
                                    <a href="#" class='btnPropoSemInteresse'>Não tenho interesse</a>
                                </div>
                            <? endif ?>
                        </div>
                    </div>
                <? endforeach; ?>

            </div>
        <? endif ?>

    </div>
</div>
