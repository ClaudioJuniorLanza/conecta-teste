<?php

class eCliente extends dDbRow3
{
    static function buildStruct()
    {
        self::setTable('ec_clientes');
        self::addField('id,data_cadastro,data_lastlogin,data_pwd_recovery,tipo');
        self::addField('nome,razao_social,responsavel_nome,responsavel_departamento,sexo');
        self::addField('data_nasc,cpf_cnpj,rg_ie,email,fone1,fone2,no_whatsapp,ramo_atividade,senha');
        self::addField('facebook_id,disabled');

        self::addField('id,clien_id,descricao,nome,cep,uf,cidade,bairro,logradouro,numero');
        self::addField('complemento,referencia,ordem');


        self::addValidation('data_cadastro', 'required', false, 'Você precisa preencher o campo data cadastro');
        self::addValidation('tipo', 'required', false, 'Você precisa preencher o campo tipo');
        self::addValidation('nome', 'required', false, 'Você precisa preencher o campo nome');
        self::addValidation('email', 'required', false, 'Você precisa informar o seu e-mail');
        self::addValidation('fone1', 'required', false, 'Você precisa informar um telefone');
        self::addValidation('disabled', 'required', false, 'Você precisa preencher o campo disabled');
        self::addValidation('data_nasc', 'date', 'br', 'Preencha a data de nascimento no formato dd/mm/aaaa');
        self::addValidation('data_cadastro', 'datetime', 'br', 'Preencha a data no formato dd/mm/aaaa hh:mm');
        self::addValidation('data_pwd_recovery', 'datetime', 'br', 'Preencha a data no formato dd/mm/aaaa hh:mm');
        self::addValidation('data_lastlogin', 'datetime', 'br', 'Preencha a data no formato dd/mm/aaaa hh:mm');
        self::addValidation('email', 'email', false, 'Por favor, informe um e-mail válido');
        self::addValidation('tipo', 'regex', '(pf|pj)', 'Opção inválida para tipo');
        self::addValidation('sexo', 'regex', '(m|f)', 'Opção inválida para sexo');
        self::addValidation('disabled', 'regex', '(0|1)', 'Opção inválida para disabled');
        self::addValidation('nome', 'singleline', false, 'O campo nome não pode ter mais de uma linha');
        self::addValidation('razao_social', 'singleline', false, 'O campo razao social não pode ter mais de uma linha');
        self::addValidation('responsavel_nome', 'singleline', false,
            'O campo responsavel nome não pode ter mais de uma linha');
        self::addValidation('responsavel_departamento', 'singleline', false,
            'O campo responsavel departamento não pode ter mais de uma linha');
        self::addValidation('cpf_cnpj', 'singleline', false, 'O campo CPF/CNPJ não pode ter mais de uma linha');
        self::addValidation('rg_ie', 'singleline', false, 'O campo RG/IE não pode ter mais de uma linha');
        self::addValidation('email', 'singleline', false, 'O campo e-mail não pode ter mais de uma linha');
        self::addValidation('fone1', 'singleline', false, 'O campo telefone não pode ter mais de uma linha');
        self::addValidation('fone2', 'singleline', false, 'O campo telefone não pode ter mais de uma linha');
        self::addValidation('ramo_atividade', 'singleline', false,
            'O campo ramo atividade não pode ter mais de uma linha');
        self::addValidation('senha', 'singleline', false, 'O campo senha não pode ter mais de uma linha');
        self::addValidation('facebook_id', 'singleline', false, 'O campo facebook id não pode ter mais de uma linha');
        self::addValidation('nome', 'strmax', 150, 'O campo nome não pode ter mais de 150 caracteres');
        self::addValidation('razao_social', 'strmax', 500, 'O campo razao social não pode ter mais de 500 caracteres');
        self::addValidation('responsavel_nome', 'strmax', 180,
            'O campo responsavel nome não pode ter mais de 180 caracteres');
        self::addValidation('responsavel_departamento', 'strmax', 180,
            'O campo responsavel departamento não pode ter mais de 180 caracteres');
        self::addValidation('cpf_cnpj', 'strmax', 25, 'O campo CPF/CNPJ não pode ter mais de 25 caracteres');
        self::addValidation('rg_ie', 'strmax', 50, 'O campo RG/IE não pode ter mais de 50 caracteres');
        self::addValidation('email', 'strmax', 150, 'O campo e-mail não pode ter mais de 150 caracteres');
        self::addValidation('fone1', 'strmax', 150, 'O campo telefone não pode ter mais de 150 caracteres');
        self::addValidation('fone2', 'strmax', 150, 'O campo telefone não pode ter mais de 150 caracteres');
        self::addValidation('ramo_atividade', 'strmax', 180,
            'O campo ramo atividade não pode ter mais de 180 caracteres');
        self::addValidation('facebook_id', 'strmax', 100, 'O campo facebook id não pode ter mais de 100 caracteres');
        self::addValidation(false, 'callback', function ($obj) {  // Valida os campos opcionais para PF e PJ
            // Dependendo do tipo de cadastro, alguns campos são obrigatórios e outros não.
            if (!$obj->v('tipo')) {
                // Se não houver tipo definido, deixe que a validação em tipo:required informe o usuário.
                return true;
            }

            $hasChanged = $obj->getAliasChanged();
            $_failed = false;
            if ($hasChanged !== false && $obj->v('tipo') == 'pj') {
                if (($hasChanged === true || in_array('razao_social',
                            $hasChanged)) && dDbRow3::sValFailed($obj->v('razao_social'), 'required')) {
                    $obj->addError('razao_social', "O campo Razão Social é obrigatório");
                    $_failed = true;
                }
                if (($hasChanged === true || in_array('responsavel_nome',
                            $hasChanged)) && dDbRow3::sValFailed($obj->v('responsavel_nome'), 'required')) {
                    $obj->addError('responsavel_nome', "O campo Nome do Responsável é obrigatório");
                    $_failed = true;
                }
                if (($hasChanged === true || in_array('cpf_cnpj',
                            $hasChanged)) && dDbRow3::sValFailed($obj->v('cpf_cnpj'), 'required')) {
                    $obj->addError('cpf_cnpj', "Por favor, informe o CNPJ da sua empresa.");
                    $_failed = true;
                }
                if (($hasChanged === true || in_array('cpf_cnpj',
                            $hasChanged)) && dDbRow3::sValFailed($obj->v('cpf_cnpj'), 'cnpj')) {
                    $obj->addError('cpf_cnpj', "Você digitou um CNPJ incorreto, por favor confira e tente novamente.");
                    $_failed = true;
                }
            } elseif ($hasChanged !== false && $obj->v('tipo') == 'pf') {
                // if(($hasChanged === true || in_array('data_nasc', $hasChanged)) && dDbRow3::sValFailed($obj->v('data_nasc'),                'required')){
                // 	$obj->addError('data_nasc', "O campo Data de Nascimento é obrigatório");
                // 	$_failed = true;
                // }
                // if(($hasChanged === true || in_array('sexo', $hasChanged)) && dDbRow3::sValFailed($obj->v('sexo'),                     'required')){
                // 	$obj->addError('sexo', "Por favor, informe o seu sexo.");
                // 	$_failed = true;
                // }
                // if(($hasChanged === true || in_array('cpf_cnpj', $hasChanged)) && dDbRow3::sValFailed($obj->v('cpf_cnpj'),                 'required')){
                // 	$obj->addError('cpf_cnpj', "Por favor, informe o seu CPF.");
                // 	$_failed = true;
                // }
                if (($hasChanged === true || in_array('cpf_cnpj',
                            $hasChanged)) && dDbRow3::sValFailed($obj->v('cpf_cnpj'), 'cpf')) {
                    $obj->addError('cpf_cnpj', "Você digitou um CPF incorreto, por favor confira e tente novamente.");
                    $_failed = true;
                }
            }

            return !$_failed;
        });

        // Validações unique:
        self::addValidation('facebook_id', 'callback', function ($obj, $value) {
            if (!trim($value)) {
                // Está em branco, deixe a validação 'required' cuidar do assunto.
                return true;
            }
            if ($obj->getVirtual('loginType') == 'noPassword') {
                // É um cadastro sem senha, não precisa ser e-mail único.
                return true;
            }
            if ($obj->isLoaded() && $obj->getOriginal('facebook_id') == $obj->getValue('facebook_id')) {
                // Sem alterações, não preciso validar.
                return true;
            }

            $db = eCliente::getDb();
            $sqlVal = $obj->modApply('basic2sql', 'facebook_id', $value);
            $exItem = $db->singleLine("select id from ec_clientes where facebook_id = {$sqlVal} limit 1");
            if (!$exItem) {
                // E-mail não estava cadastrado, novo cadastro.
                return true;
            }

            $obj->addError('facebook_id', "FACEBOOK_EXISTS");
            return false;
        });
        self::addValidation('email', 'callback', function ($obj, $value) {
            if (!trim($value)) {
                // Está em branco, deixe a validação 'required' cuidar do assunto.
                return true;
            }
            if ($obj->getVirtual('loginType') == 'noPassword') {
                // É um cadastro sem senha, não precisa ser e-mail único.
                return true;
            }
            if ($obj->isLoaded() && $obj->getOriginal('email') == $obj->getValue('email')) {
                // Sem alterações, não preciso validar.
                return true;
            }

            $db = eCliente::getDb();
            $sqlVal = $obj->modApply('basic2sql', 'email', $value);
            $exItem = $db->singleLine("select id,facebook_id from ec_clientes where email = {$sqlVal} limit 1");
            if (!$exItem) {
                // E-mail não estava cadastrado, novo cadastro.
                return true;
            }
            if ($exItem['id'] == $obj->v('id')) {
                // O ID informado é do próprio cliente.. Estranho, mas vamos aceitar.
                return true;
            }

            $obj->addError('email', "EMAIL_EXISTS" . ($exItem['facebook_id'] ? "_WITH_FACEBOOK" : ""));
            return false;
        });
        self::addValidation('cpf_cnpj', 'callback', function ($obj, $value) {
            if (!trim($value)) {
                // Está em branco, deixe a validação 'required' cuidar do assunto.
                return true;
            }
            if ($obj->getVirtual('loginType') == 'noPassword') {
                // É um cadastro sem senha, não precisa ser e-mail único.
                return true;
            }
            if ($obj->isLoaded() && $obj->getOriginal('cpf_cnpj') == $obj->getValue('cpf_cnpj')) {
                // Sem alterações, não preciso validar.
                return true;
            }

            $db = eCliente::getDb();
            $sqlVal = $obj->modApply('basic2sql', 'cpf_cnpj', $value);
            $exItem = $db->singleLine("select id,facebook_id from ec_clientes where cpf_cnpj = {$sqlVal} limit 1");
            if (!$exItem) {
                // E-mail não estava cadastrado, novo cadastro.
                return true;
            }
            if ($exItem['id'] == $obj->v('id')) {
                // O ID informado é do próprio cliente.. Estranho, mas vamos aceitar.
                return true;
            }

            $obj->addError('cpf_cnpj', "CPF_EXISTS" . ($exItem['facebook_id'] ? "_WITH_FACEBOOK" : ""));
            return false;
        });

        self::addModifier('fone1,fone2', 'callback', function ($obj, $str) {
            return dHelper2::formataTelefone($str);
        });
        self::addModifier('data_cadastro', 'datetime', 'br');
        self::addModifier('data_lastlogin,data_pwd_recovery', 'datetime', 'br');
        self::addModifier('data_nasc', 'date', 'br');
        self::addModifier('nome,responsavel_nome', 'ucfirst');
        self::addModifier('nome,razao_social,responsavel_nome,responsavel_departamento', 'trim');
        self::addModifier('cpf_cnpj,rg_ie,email,fone1,fone2,ramo_atividade,senha,facebook_id', 'trim');
        self::addModifier('cpf_cnpj', 'force_numbers');
        self::addModifier('email', 'lower');
        self::addModifier('nome', 'callback', function ($obj, $str) {
            return dHelper2::ucWordsBr($str);
        });

        /**
         * $senha = substr($senha, 0, 30);
         * $senha = md5($senha.$id*478)
         **/
        $_passwordEmpty = "** criptografada **";
        self::addValidation('senha', 'callback', function ($obj, $password) use ($_passwordEmpty) {
            if ($obj->getVirtual('senha-donthash')) {
                // Se pediu para não hashear a senha, é porque já está passando uma
                // senha tratada. Dessa forma, aceite o valor fornecido sem reclamar.
                return true;
            }
            if ($obj->getVirtual('loginType') == 'noPassword') {
                // Se tiver o virtual '_ignorePassword', ignora a validação da senha...
                return true;
            }
            if (!strlen($obj->getValue('senha')) && !strlen($obj->getValue('facebook_id'))) {
                $obj->addError('senha', "Para continuar, informe uma senha.");
                return false;
            }

            // Lembrando que a validação de senha ocorre apenas com os dados no formato 'basic', antes
            // de serem chamados para o banco de dados.
            if (!$password) {
                // Podemos aceitar senha em branco, se houver um facebook_id (verificação feita anteriormente).
                return true;
            }
            if ($password == $_passwordEmpty) {
                // Nenhuma mudança, vamos aceitar sem validação.
                return true;
            }

            $_failed = false;
            if (dDbRow3::sValFailed($password, 'strmin', 4)) {
                $obj->addError('senha', "Por favor, preencha uma senha com no mínimo 4 caracteres.");
                $_failed = true;
            }
            if (dDbRow3::sValFailed($password, 'regex', '[a-zA-Z]')) {
                $obj->addError('senha', "Pedimos que você tenha ao menos UMA letra na sua senha.");
                $_failed = true;
            }
            if (dDbRow3::sValFailed($password, 'regex', '[1-9]')) {
                $obj->addError('senha', "Pedimos que você tenha pelo menos UM número na sua senha.");
                $_failed = true;
            }

            if (!$_failed && $obj->hasVirtual('resenha')) {
                if (!trim($obj->getVirtual('resenha'))) {
                    $obj->addError('resenha', "Repita a senha, para confirmar que você digitou corretamente.");
                    $_failed = true;
                } elseif ($password != trim($obj->getVirtual('resenha'))) {
                    $obj->addError('senha',
                        "A senha e a confirmação de senha não conferem. Por favor, re-digite ambos.");
                    $_failed = true;
                }
            }
            return !$_failed;
        });
        self::addModifier('senha', 'callback', function ($obj, $password, $when) use ($_passwordEmpty) {
            if ($when == 'raw2basic') {
                $password = trim($password);
                return strlen($password) ? $password : false;
            }
            if ($when == 'basic2db') {
                if (!strlen($password)) {
                    return false;
                }
                if ($password == $_passwordEmpty) {
                    // Não alterou a senha, ela se mantém como $_passwordEmpty.
                    return $password;
                }
                if ($obj->getVirtual('senha-donthash')) {
                    $obj->removeVirtual('senha-donthash');
                    $obj->v('senha', $_passwordEmpty);
                    return $password;
                }

                // Se solicitar getValue no formato 'basic' a partir deste ponto, já deve ser empty de novo.
                $obj->setValue('senha', $_passwordEmpty);

                // Mas como estamos lidando com basic2db, precisamos armazenar o hash...
                $senha = md5($password . 'cUsuario' . dSystem::getGlobal('hashkey'));
                # echo "- passwordDebug: Primeira criptografia é: {$senha}.<br />";

                $senha = substr($senha, 0, 30) . "OH";
                # echo "- passwordDebug: Valor a ser saltado é: {$senha}.<br />";

                // Se for um usuário logado, podemos saltear com o 'id' e obter a string final.
                // Se for um novo usuário, vamos adicionar _pendingPasswordSalt, para montar o
                // hash final depois que houver um id válido.
                if ($obj->v('id')) {
                    $senha = md5($senha . ($obj->v('id') * 478));
                    # echo "- passwordDebug: Post-salt ficou como: {$senha}.<br />";
                } else {
                    $obj->setVirtual('_pendingPasswordSalt', $senha);
                }

                return $senha;
            }
            if ($when == 'db2basic') {
                return $password ?
                    $_passwordEmpty :
                    false;
            }
        }, 'raw2basic,basic2db,db2basic');
        self::addEventListener('afterCreate', function ($obj) {
            if ($prevSenha = $obj->getVirtual('_pendingPasswordSalt')) {
                # echo "- passwordDebug: Aplicando _pendingPasswordSalt à {$prevSenha}.<br />";
                $obj->removeVirtual('_pendingPasswordSalt');
                $newSenha = md5($prevSenha . $obj->v('id') * 478);
                # echo "- passwordDebug: Resultado final ficou {$newSenha}<br />";
                $obj->setVirtual('senha-donthash', true);
                $obj->v('senha', $newSenha)->save();
            }
        });

        self::setDefaultValue('data_cadastro', date('d/m/Y H:i:s'));
        self::setDefaultValue('disabled', '0');
        self::setDefaultValue('no_whatsapp', '0');

        self::addExt('enderObj', array(
            'className' => 'eClienEndereco',
            'thisKey' => 'id',
            'targetKey' => 'clien_id',
            'reverseKey' => 'clienObj',
            'joinMode' => 'left',
            'extraOn' => " and ordem='1'",
        ));

        self::setAuditing(array('dAuditObjeto', 'cbAuditCallback'));
    }

    function writeCard($asHtml = false)
    {
        $text = "<table cellpadding='4' cellspacing='0' border='0'>";
        $text .= "<tr><td colspan='2'><b>" . htmlspecialchars($this->v('nome')) . "</b></td></tr>\r\n";
        if ($this->v('razao_social')) {
            $text .= "<tr><td><b>Razão Social:</b></td><td>" . htmlspecialchars($this->v('razao_social')) . "</td></tr>\r\n";
        }
        if ($this->v('ramo_atividade')) {
            $text .= "<tr><td><b>Ramo de Atividade:</b></td><td>" . htmlspecialchars($this->v('ramo_atividade')) . "</td></tr>\r\n";
        }
        if ($this->v('responsavel_nome')) {
            $text .= "<tr><td><b>Responsável:</b></td><td>" . htmlspecialchars($this->v('responsavel_nome')) . "</td></tr>\r\n";
        }
        if ($this->v('responsavel_departamento')) {
            $text .= "<tr><td><b>Cargo/Setor:</b></td><td>" . htmlspecialchars($this->v('responsavel_departamento')) . "</td></tr>\r\n";
        }
        if ($this->v('email')) {
            $text .= "<tr><td><b>E-mail:</b></td><td>" . htmlspecialchars($this->v('email')) . "</td></tr>\r\n";
        }
        if ($this->v('fone1')) {
            $text .= "<tr><td><b>Telefone:</b></td><td>" . htmlspecialchars($this->v('fone1')) . "</td></tr>\r\n";
        }
        if ($this->v('fone2')) {
            $text .= "<tr><td><b>Telefone 2:</b></td><td>" . htmlspecialchars($this->v('fone2')) . "</td></tr>\r\n";
        }
        if ($this->v('no_whatsapp')) {
            $text .= "<tr><td colspan='2'><i>* Marcou não ter WhatsApp</i></td></tr>\r\n";
        }
        $text .= "</table>";

        return $asHtml ?
            $text :
            html_entity_decode(strip_tags($text));
    }

    // Recuperação de senha:
    // ---------------------------------------------------
    public function getRecoveryLink()
    {
        $this->v('data_pwd_recovery', date('d/m/Y H:i:s'))->save();

        $useKey = md5($this->v('id') . $this->v('data_pwd_recovery') . '-xh') . $this->v('id');

        $useLink = dSystem::getGlobal('baseUrl');
        $useLink .= "minha_conta_senha.php";
        $useLink .= "?key={$useKey}";

        return $useLink;
    }

    static function handleRecoveryAccess($key = false)
    {
        if (!$key) {
            $key = $_GET['key'];
        }

        $checkKey = substr($key, 0, 32);
        $clienId = substr($key, 32);
        if (!$key || !$checkKey || !$clienId) {
            return "InvalidHash";
        }

        $clienObj = eCliente::load($clienId);
        if (!$clienObj) {
            return "InvalidHash";
        }
        if (!$clienObj->v('data_pwd_recovery')) {
            return "InvalidHash";
        }

        $expecKey = md5($clienObj->v('id') . $clienObj->v('data_pwd_recovery') . '-xh') . $clienObj->v('id');
        if ($key != $expecKey) {
            return "InvalidHash";
        }
        if (strtotime($clienObj->getModValue('data_pwd_recovery', 'db') . " +24 hours") < time()) {
            return "LinkExpired";
        }

        return $clienObj;
    }

    // Login:
    // ---------------------------------------------------
    static $loggedObj = false;

    static function loginWithFacebook($facebookId)
    {
        $clienObj = self::load(array('cbMakeQuery' => "where facebook_id='{$facebookId}'"));
        if ($clienObj) {
            return self::setLogged($clienObj, 'facebook');
        }
        return false;
    }

    static function loginWithPassword($emailOrCpf, $password)
    {
        if (!trim($password)) {
            return false;
        }
        $clienObj = self::searchUser($emailOrCpf, $password);
        if ($clienObj) {
            return self::setLogged($clienObj, 'default');
        }
        return false;
    }

    static function setLogged($clienObj, $loginType = 'default')
    {
        $_SESSION['Cliente'] = array(
            'LoginType' => $loginType,
            'LoginId' => $clienObj->v('id'),
        );
        $clienObj->setVirtual('loginType', $loginType);
        $clienObj->setValue('data_lastlogin', date('d/m/Y H:i:s'))->save();
        return self::$loggedObj = $clienObj;
    }

    static function searchUser($emailOrCpf, $tryPassword = false)
    {
        $useUsername = (strpos($emailOrCpf, "@") !== false) ?
            "email = '" . addslashes(trim($emailOrCpf)) . "'" :
            "cpf_cnpj = '" . preg_replace("/[^0-9]/", "", $emailOrCpf) . "'";

        $sqlWhere = "WHERE {$useUsername}";
        if ($tryPassword !== false) {
            /**
             * $senha = substr($senha, 0, 30).'OH';
             * $senha = md5($senha.$id*1239)
             **/

            $usePassword = trim($tryPassword);
            $usePassword = substr(md5($usePassword . 'eCliente' . dSystem::getGlobal('hashkey')), 0, 30) . 'OH';
            $usePassword = "MD5(concat('{$usePassword}', ec_clientes.id*1239))";
            $sqlWhere .= " and senha = {$usePassword}";
        }
        $sqlWhere .= " limit 1";
        return self::load(array('cbMakeQuery' => $sqlWhere));
    }

    static function isLogged($allowNoPassword = false)
    {
        if (self::$loggedObj) {
            if (!$allowNoPassword && !self::$loggedObj->isLoaded()) {
                return false;
            }
            return self::$loggedObj;
        }

        $sessLoginType = @$_SESSION['Cliente']['LoginType'];
        if ($sessLoginType == 'noPassword' && $allowNoPassword) {
            $clienObj = new eCliente(false, false);
            $clienObj->loadArray($_SESSION['Cliente']['Dados']['Cliente']);
            $clienObj->setVirtual('loginType', 'noPassword');

            $enderObj = $clienObj->getValue('enderObj', true);
            $enderObj->loadArray($_SESSION['Cliente']['Dados']['Endereco']);

            if (!$enderObj->v('cep') && isset($_SESSION['Pedido']['freteCep'])) {
                $enderObj->v('cep', $_SESSION['Pedido']['freteCep']);
            }

            return self::$loggedObj = $clienObj;
        } else {
            $sessLoginId = @$_SESSION['Cliente']['LoginId'];
            if (!$sessLoginId) {
                // Tem uma sessão do facebook iniciada?
                $facebookObj = dFacebook::start('ecnextClient');
                if ($facebookObj->isLogged() && ($me = $facebookObj->graphRequest('/me'))) {
                    return eCliente::loginWithFacebook($me['id']);
                }
                return false;
            }

            $clienObj = self::load($sessLoginId, 'enderObj');
            if (!$clienObj) {
                return false;
            }

            $clienObj->setVirtual('loginType', $sessLoginType ? $sessLoginType : 'default');
        }

        return self::$loggedObj = $clienObj;
    }

    static function isLoggedOrRedirect($allowNoPassword = false)
    {
        $ret = self::isLogged($allowNoPassword);
        if (!$ret) {
            $_SESSION['ClienteAfterLoginGoTo'] = $_SERVER['REQUEST_URI'];
            dHelper2::redirectTo(dSystem::getGlobal('baseUrl') . 'login.php' . ($allowNoPassword ? '?anp=1' : ''));
        }
        return $ret;
    }

    static function logOut($inclFacebook = true)
    {
        if ($inclFacebook) {
            $facebookObj = dFacebook::start('ecnextClient');
            $facebookObj->logOut();
        }

        unset($_SESSION['Cliente']);
        self::$loggedObj = false;
    }
}


