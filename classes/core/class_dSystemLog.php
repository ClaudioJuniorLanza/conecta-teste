<?php // built on 13/03/2015 10:13
class dSystemLog extends dDbRow3
{
    static function buildStruct()
    {
        self::setTable('d_systemlogs');
        self::addField('id,data_hora,level,evento,conteudo');

        self::addValidation('data_hora', 'required', false, 'Você precisa preencher o campo data hora');
        self::addValidation('level', 'required', false, 'Você precisa preencher o campo level');
        self::addValidation('evento', 'required', false, 'Você precisa preencher o campo evento');
        self::addValidation('data_hora', 'datetime', 'br', 'Preencha a data no formato dd/mm/aaaa hh:mm');
        self::addValidation('evento', 'singleline', false, 'O campo evento não pode ter mais de uma linha');
        self::addValidation('evento', 'strmax', 250, 'O campo evento não pode ter mais de 250 caracteres');

        self::addModifier('data_hora', 'datetime', 'br');
        self::addModifier('evento,conteudo', 'trim');

        self::addModifier('level', 'upper');
        self::addModifier('evento', 'callback', function ($obj, $value, $when) {
            if (strlen($value) > 250) {
                $obj->v('conteudo', "{$value}\r\n\r\n{$obj->v('evento')}", 'basic');
                $value = substr($value, 0, 247) . '...';
            }
            return $value;
        }, 'raw2basic');

        self::addValidation('level', 'callback', function ($obj, $value, $when) {
            if (!in_array($value, array('LOW', 'MED', 'HIGH', 'CRITICAL', 'NOTICE'))) {
                $obj->v('level', 'MED', 'basic');
                $obj->v('conteudo',
                    "O level '{$value}' foi alterado para 'MED', pois não era reconhecido.\r\n" .
                    "Os níveis de alerta permitidos são: NOTICE, LOW, MED, HIGH, CRITICAL.\r\n" .
                    "\r\n" .
                    $obj->v('conteudo')
                );
            }
            return true;
        });

        self::addToConstructor(function ($obj) {
            $obj->v('data_hora', date('d/m/Y H:i:s'));
        });

        self::setAuditing(array('dAuditObjeto', 'cbAuditCallback'));
    }

    static function log($level, $evento, $descricao = false)
    {
        if (dSystem::getGlobal('currentVersion') < 1.4) {
            // Se estamos abaixo da versão 1.4, significa que ainda não temos
            // a tabela do banco de dados criada. Dessa forma, não temos como
            // armazenar informações ali.
            return false;
        }

        // Ps:
        // --> No caso de SQL Error (exemplo, a tabela não foi criada, ou qualquer outro motivo),
        //     a classe dDatabase tem seu próprio método "onSqlErrorMailTo", responsável por notificar
        //     o administrador. Dessa forma, não há risco de loop infinito, já que tal notificação não
        //     é realizada pelo dSystem::notifyAdmin, que por sua vez chamaria novamente o self::log,
        //     mas sim por um método externo.
        //
        // --> Se um dia o dDatabase for integrado ao dSystem::notifyAdmin, então esta classe deverá ser
        //     revisada, de forma a evitar loops e notificações infinitas.
        //
        $obj = new dSystemLog;
        $obj
            ->v('level', $level)
            ->v('evento', $evento)
            ->v('conteudo', $descricao)
            ->save();

        if (!$obj->v('id')) {
            $obj2 = new dSystemLog;
            $obj2
                ->v('level', 'CRITICAL')
                ->v('evento', 'Falha ao salvar evento (log)')
                ->v('conteudo',
                    "Houve uma falha crítica ao tentar salvar um evento em dSystemLog::log().\r\n" .
                    "\r\n" .
                    "O erro retornado foi:\r\n" .
                    implode("\r\n", $obj->listErrors(true)) . "\r\n" .
                    "\r\n"
                )
                ->save();
        }
    }
}

?>
