<?php

class dDbRow_Sorting extends dDbRow
{
    private $relatedFields;

    function sortingSetRelated($relatedFields = array())
    {
        $this->relatedFields = $relatedFields;
    }

    // Sorting
    function sortingGetRelated($asWhere = false)
    {
        $ret = array();
        $ret = $this->relatedFields;

        /**
         * Importante - Limite: 4
         * Para outro limite, edite o método sortingRefresh().
         **/

        if ($ret && $asWhere) {
            $str = array();
            foreach ($ret as $key) {
                $compareTo = $this->formatToQuery($this->getOriginal($key), $key);
                if (strtolower($compareTo) == "null") {
                    $str[] = "isnull({$this->useQuotes}{$key}{$this->useQuotes})";
                } else {
                    $str[] = "{$this->useQuotes}{$key}{$this->useQuotes} = {$compareTo}";
                }
            }
            return implode(" and ", $str);
        }
        return $ret;
    }

    function sortingMoveToTop()
    {
        return $this->sortingMoveTo(1);
    }

    function sortingMoveToBottom()
    {
        return $this->sortingMoveTo('MAX');
    }

    function sortingMoveTo($new)
    {
        $db = dDatabase::start();
        $tb = $this->mainTable["tableName"];
        $wh = $this->sortingGetRelated(true);
        $max = intval($db->singleResult("select max(ordem) from {$tb}" . ($wh ? " where {$wh}" : "")));
        $old = $this->getOriginal('ordem');

        $this->castMsg("Max from database: OLD={$old}, MAX={$max}. Where: {$wh}");

        if (strtoupper($new) == 'MAX') {
            $new = ($max + 1);
        }

        $new = intval($new);
        if ($new < 1) {
            $new = 1;
        }

        if (!$old) {
            $max++;
            $this->castMsg("Detectado novo registro pois não possui OLD. Max com este objeto agora é {$max}");
            if ($new > $max) {
                $new = $max;
            } elseif ($new < $max) {
                $db->query("update {$tb} set ordem = ordem+1 where " . ($wh ? "{$wh} and" : "") . " ordem >= '$new'",
                    "Novo item entrando no meio");
            }
        } else {
            if ($new == $old) {
                return $new;
            }

            if ($new > $max) {
                $new = $max;
            }

            # echo "Nova posição: {$new}<br />";
            # echo "Old position: {$old}<br />";
            # echo "Max position: {$max}<br />";

            if ($new < $old) {
                $db->query("update {$tb} set ordem = ordem+1 where " . ($wh ? "{$wh} and" : "") . " ordem >= '$new' and ordem <  '$old'",
                    "Altera a posição dos registros antes deste.");
            }
            if ($new > $old) {
                $db->query("update {$tb} set ordem = ordem-1 where " . ($wh ? "{$wh} and" : "") . " ordem > '$old'  and ordem <= '$new'",
                    "Altera a posição dos registros depois deste.");
            }

        }
        $db->query("update {$tb} set ordem='{$new}' where {$this->mainTable['primaryIndex']}='{$this->getPrimaryValue()}'",
            "Atualiza a posição do item");
        return $new;
    }

    function sortingPrepareToDelete()
    {
        // Considera os campos 'originais'.

        $db = dDatabase::start();
        $tb = $this->mainTable["tableName"];
        $old = $this->getOriginal('ordem');
        $wh = $this->sortingGetRelated(true);
        if ($old) {
            $db->query("update {$tb} set ordem = ordem-1 where " . ($wh ? "{$wh} and" : "") . " ordem > '$old'",
                "Remove a 'Ordem' do produto e prepara para exclusão");
            $this->setValue('ordem', false);
        }
    }

    function sortingRefresh()
    {
        $db = dDatabase::start();
        $tb = $this->mainTable["tableName"];
        $wh = $this->sortingGetRelated();

        if (!$wh) {
            $n = 1;
            $allObjs = $db->singleColumn("select id from {$tb} order by ordem");
            foreach ($allObjs as $objId) {
                $db->query("update {$tb} set ordem='" . ($n++) . "' where id='{$objId}'");
            }

            return true;
        } else {
            // Como fazer? KISS!
            // Sejamos honestos, quantas vezes vai haver mais que quatro filtros?! É impossível!
            $whereToCheck = array();

            if (sizeof($wh) == 1) {
                $allValuesForKey0 = $db->singleColumn("select distinct {$wh[0]} from {$tb}");
                foreach ($allValuesForKey0 as $updateMe0) {
                    $whereToCheck[] = "{$wh[0]}=" . $this->formatToQuery($updateMe0, $wh[0]);
                }
            }
            if (sizeof($wh) == 2) {
                $allValuesForKey0 = $db->singleColumn("select distinct {$wh[0]} from {$tb}");
                $allValuesForKey1 = $db->singleColumn("select distinct {$wh[1]} from {$tb}");
                foreach ($allValuesForKey0 as $updateMe0) {
                    foreach ($allValuesForKey1 as $updateMe1) {
                        $whereToCheck[] =
                            "{$wh[0]}=" . $this->formatToQuery($updateMe0, $wh[0]) . " and " .
                            "{$wh[1]}=" . $this->formatToQuery($updateMe1, $wh[1]);
                    }
                }
            }
            if (sizeof($wh) == 3) {
                $allValuesForKey0 = $db->singleColumn("select distinct {$wh[0]} from {$tb}");
                $allValuesForKey1 = $db->singleColumn("select distinct {$wh[1]} from {$tb}");
                $allValuesForKey2 = $db->singleColumn("select distinct {$wh[2]} from {$tb}");
                foreach ($allValuesForKey0 as $updateMe0) {
                    foreach ($allValuesForKey1 as $updateMe1) {
                        foreach ($allValuesForKey2 as $updateMe2) {
                            $whereToCheck[] =
                                "{$wh[0]}=" . $this->formatToQuery($updateMe0, $wh[0]) . " and " .
                                "{$wh[1]}=" . $this->formatToQuery($updateMe1, $wh[1]) . " and " .
                                "{$wh[2]}=" . $this->formatToQuery($updateMe2, $wh[2]);
                        }
                    }
                }
            }
            if (sizeof($wh) == 4) {
                $allValuesForKey0 = $db->singleColumn("select distinct {$wh[0]} from {$tb}");
                $allValuesForKey1 = $db->singleColumn("select distinct {$wh[1]} from {$tb}");
                $allValuesForKey2 = $db->singleColumn("select distinct {$wh[2]} from {$tb}");
                $allValuesForKey3 = $db->singleColumn("select distinct {$wh[3]} from {$tb}");
                foreach ($allValuesForKey0 as $updateMe0) {
                    foreach ($allValuesForKey1 as $updateMe1) {
                        foreach ($allValuesForKey2 as $updateMe2) {
                            foreach ($allValuesForKey3 as $updateMe3) {
                                $whereToCheck[] =
                                    "{$wh[0]}=" . $this->formatToQuery($updateMe0, $wh[0]) . " and " .
                                    "{$wh[1]}=" . $this->formatToQuery($updateMe1, $wh[1]) . " and " .
                                    "{$wh[2]}=" . $this->formatToQuery($updateMe2, $wh[2]) . " and " .
                                    "{$wh[3]}=" . $this->formatToQuery($updateMe2, $wh[3]);
                            }
                        }
                    }
                }
            }
            if (sizeof($wh) > 4) {
                $this->castDbg(2, "Limite de filtros é 4. Edite o este método automaticamente.");
                return false;
            }

            foreach ($whereToCheck as $where) {
                $n = 1;
                $allObjs = $db->singleColumn("select id from {$tb} where {$where} order by ordem");
                foreach ($allObjs as $objId) {
                    $db->query("update {$tb} set ordem='" . ($n++) . "' where id='{$objId}'");
                }
            }
        }
    }

    function saveToDatabase($pid = false)
    {
        // Antes e depois de salvar, modifique campo "Ordem" dos objetos semelhantes.
        $oldPos = $this->getOriginal('ordem');
        $newPos = $this->getValue('ordem');

        $this->castMsg("Salvando... OldPos={$oldPos}, NewPos={$newPos}");

        if (!$this->getPrimaryValue()) { // Não estava carregado...
            if ($pid) {
                $this->castMsg("Tentando forçar um load...");
                // Mas tentou substituir algo!
                // Então tente carregar esse algo e tente novamente...

                // 1. Pegar valores que era pra salvar...
                $newValues = array();
                foreach ($this->fieldProps as $aliasName => $prop) {
                    $newValues[$aliasName] = array($this->fieldValues[$aliasName], $prop['raw']);
                }

                // 2. Carregar do banco de dados...
                if ($this->loadFromDatabase($pid)) {
                    // 3. Substituir do database pelos dados que era pra salvar no início
                    foreach ($newValues as $key => $value) {
                        $this->setValue($key, $value[0], $value[1]);
                    }
                }

                unset($newValues);

                $oldPos = $this->getOriginal('ordem');
                $newPos = $this->getValue('ordem');
            }
            if (!$this->getPrimaryValue()) { // Se continua não carregado, ou seja: é um novo item!
                $this->castMsg("É objeto novo!");
                // Salvar e, se tiver o valor de 'ordem', definir a posição!
                // Se não tiver, mover para o final da lista.
                $this->setValue('ordem', false);

                $ret = parent::saveToDatabase($pid);
                if ($ret && $newPos) {
                    $this->castMsg("Tem posição definida, utilizando ela!");
                    $this->sortingMoveTo($newPos);
                } elseif ($ret) {
                    $this->castMsg("Não foi informada uma posição, movendo para o final.");
                    $this->sortingMoveToBottom();
                }

                return $ret;
            }
        }

        // Se chegou até aqui, é um objeto existente e carregado, e possui getValue e getOriginal.
        $this->castMsg("Objeto já carregado, tentando atualizar posição (OldPos={$oldPos}, NewPos={$newPos})");

        // Tem modificação em campos importantes?
        $isKeyChanged = false;
        $checkKeys = $this->sortingGetRelated();
        if ($checkKeys) {
            foreach ($checkKeys as $key) {
                if ($this->isModified($key)) {
                    $isKeyChanged = true;
                    break;
                }
            }
        }

        $this->castMsg("Tem modificação em campo importante? " . ($isKeyChanged ? 'Sim' : 'Nao'));

        if ($isKeyChanged) { // Se houver modificação em campos importantes...
            $this->sortingPrepareToDelete();
            $ret = parent::saveToDatabase($pid);
            if ($ret) {
                // Salvou e tem uma posição...
                $this->castMsg("Salvei com sucesso, definindo nova posição: {$newPos}");
                if ($newPos) {
                    $this->sortingMoveTo($newPos);
                } else {
                    $this->sortingMoveToBottom();
                }
            } elseif (!$ret) {
                // Falhou e não conseguiu salvar!
                // Então volta temporariamente os campos importantes
                $newValues = array();
                foreach ($this->fieldProps as $aliasName => $prop) {
                    $newValues[$aliasName] = array($this->fieldValues[$aliasName], $prop['raw']);
                    $this->setValue($aliasName, $this->getOriginal($aliasName));
                }

                // Define a posição original
                $this->sortingMoveTo($oldPos);

                // E retorna novamente o objeto pra estado atual
                foreach ($newValues as $key => $value) {
                    $this->setValue($key, $value[0], $value[1]);
                }
            }
            return $ret;
        } else {              // Se não houver modificação em campos importantes...
            # --> Causando bug.. Não lembro por que existia isso!
            # $this->setValue('ordem', false);
            # $ret  = parent::saveToDatabase($pid);

            // Se mudou a posição ou o ID do item a ser salvo...
            if ($oldPos != $newPos) {
                $this->sortingMoveTo($newPos);
            }

            # Peguei o bug de cima e joguei pra cá, parece que funcionou.
            # Exige mais testes!
            $ret = parent::saveToDatabase($pid);
        }

        return $ret;
    }

    function deleteFromDatabase()
    {
        // Organiza as posições dos objetos que não serão removidos
        $this->sortingPrepareToDelete();
        return parent::deleteFromDatabase();
    }
}

