<?php

namespace Cityware\Form\Models;

class FormsDb {

    private $configFields, $configForm;

    public function getConfigFields() {
        return $this->configFields;
    }

    public function setConfigFields($configFields) {
        $this->configFields = $configFields;
    }

    public function getConfigForm() {
        return $this->configForm;
    }

    public function setConfigForm(array $configForm) {
        $this->configForm = $configForm;
    }

    /**
     * Verifica se na where contem variável PHP e prepara o mesmo ou somente define a where
     * @param string $value
     * @return string
     */
    public function prepareWherePhpTag($value) {

        if (strpos($value, "{") and strpos($value, "}")) {
            /* Pega por expressão regular as veriáveis PHP */
            preg_match_all("/'\{(.*)\}'/U", $value, $arr);
            $where = $arr2 = array();
            foreach ($arr[1] as $key2 => $value2) {
                $replace = null;
                eval('$replace = ' . $value2 . ';');
                $arr2[$key2] = "'" . $replace . "'";
            }
            if (count($arr[0]) > 1) {
                $valueTemp = $value;
                /* Monta a definição da where */
                foreach ($arr[0] as $key3 => $value3) {
                    $valueTemp = str_replace($value3, $arr2[$key3], $valueTemp);
                }
                $where = $valueTemp;
            } else {
                /* Monta a definição da where */
                foreach ($arr[0] as $key3 => $value3) {
                    $where = str_replace($value3, $arr2[$key3], $value);
                }
            }
            return $where;
        } else {
            return $value;
        }
    }

    /**
     * Função que retorna com os dados de preenchimento do formulário 
     * @return array 
     */
    public function populateForm($id) {

        $db = \Cityware\Db\Factory::factory('zend');
        $platform = $db->getAdapter()->getPlatform();

        /* Verifica o schema se está setado ou não */
        $schema = (isset($this->configForm['schema']) and ! empty($this->configForm['schema'])) ? $this->configForm['schema'] : null;

        /* Define a tabela */
        $db->from($this->configForm['table'], 'tb', $schema);

        /* Define os campos */
        //$db->select('*');

        /* Define a coluna primária e monta a condição do SELECT */
        $metadata = new \Zend\Db\Metadata\Metadata($db->getAdapter());
        $constraints = $metadata->getTable($this->configForm['table'], $schema)->getConstraints();

        $varColunaPk = null;
        foreach ($constraints as $value) {
            if ($value->getType() == 'PRIMARY KEY') {
                $varColunaPk = $value->getColumns();
            }
        }

        $db->where($platform->quoteIdentifier('tb') . "." . $platform->quoteIdentifier(reset($varColunaPk)) . ' = ' . $id);



        /* Executa o SELECT */
        $db->setDebug(false);
        $rs = $db->executeSelectQuery();

        /* Formata qualquer coluta do tipo data */
        $return = \Cityware\Format\Date::formatDate($rs, 'd/m/Y');

        /* Retorna um array com os dados de preenchimento do formulário */
        return $return[0];
    }

    /**
     * Popula campo pelo banco de dados
     * @param array $params
     * @return array
     */
    public function populateSelect($params, $arrayDepend = null, $populateValues = null) {

        $db = \Cityware\Db\Factory::factory('zend');

        /* Verifica o schema se está setado ou não */
        $schema = (isset($params['schema']) and $params['schema'] != "") ? $params['schema'] : null;

        /* Verifica foi setado um alias para a tabela de FROM */
        $aliasFromFk = (isset($params['tableAliasFk']) and ! empty($params['tableAliasFk'])) ? $params['tableAliasFk'] : 'fk';


        /* Define os campos */
        $db->select("{$aliasFromFk}.{$params['fieldfk']}", 'ID');
        $db->select("{$aliasFromFk}.{$params['fieldshow']}", 'NOME');

        /* Verifica se existe algum campo concatenado */
        if (isset($params['tableconc']) and ! empty($params['tableconc'])) {
            $db->select("{$params['tableconc']}.{$params['fieldconc']}", 'CONCAT');
        }

        /* Define a ordem de exibição dos dados */
        
        if (isset($params['order']) and $params['order'] == 'ID') {
            $db->orderBy("ID ASC");
        } else {
            $db->orderBy("NOME ASC");
        }


        /* Define a tabela */
        $db->from("{$params['tablefk']}", $aliasFromFk, $schema);

        /* Monta a condição do SELECT em caso de dependência */
        if (!empty($arrayDepend)) {
            if (isset($params['dependfk']) and ! empty($params['dependfk'])) {
                $db->where("{$params['dependfk']} = '{$arrayDepend[$params['dependfk']]}'");
            }
            if (isset($params['where']) and ! empty($params['where'])) {
                foreach ($params['where'] as $keyWhere => $valueWhere) {
                    $db->where($this->prepareWherePhpTag($valueWhere));
                }
            }
        } else
        /* Monta a condição do SELECT em caso de populate */
        if (!empty($populateValues)) {
            if (isset($params['fieldpk']) and ! empty($params['fieldpk'])) {
                $db->where("{$params['fieldpk']} = '{$populateValues[$params['fieldpk']]}'");
            }
            if (isset($params['where']) and ! empty($params['where'])) {
                foreach ($params['where'] as $keyWhere => $valueWhere) {
                    $db->where($this->prepareWherePhpTag($valueWhere));
                }
            }
        } else {
            /* Define as condições do select caso houver */
            if (isset($params['where']) and ! empty($params['where'])) {
                foreach ($params['where'] as $keyWhere => $valueWhere) {
                    $db->where($this->prepareWherePhpTag($valueWhere));
                }
            }
        }

        /* Define o JOIN da tabela caso houver */
        if (isset($params['join']) and $params['join'] == "true") {
            for ($idxJoin = 0; $idxJoin < count($params['jointype']); $idxJoin++) {
                $joinAlias = (isset($params['joinalias'][$idxJoin]) and ! empty($params['joinalias'][$idxJoin])) ? $params['joinalias'][$idxJoin] : null;
                $db->join($params['joinfrom'][$idxJoin], $joinAlias, $params['joincond'][$idxJoin], $params['jointype'][$idxJoin], $params['joinschema'][$idxJoin]);
            }
        }

        if (isset($params['debug']) and $params['debug'] == "true") {
            $db->setDebug(true);
        }

        return $db->executeSelectQuery();
    }
    
    
    /**
     * Popula campo select group pelo banco de dados
     * @param array $params
     * @return array
     */
    public function populateSelectGroup($params, $arrayDepend = null, $populateValues = null) {

        $db = \Cityware\Db\Factory::factory('zend');

        /* Verifica o schema se está setado ou não */
        $schema = (isset($params['schema']) and $params['schema'] != "") ? $params['schema'] : null;

        /* Verifica foi setado um alias para a tabela de FROM */
        $aliasFromFk = (isset($params['tableAliasFk']) and ! empty($params['tableAliasFk'])) ? $params['tableAliasFk'] : 'fk';


        /* Define os campos */
        $db->select("{$aliasFromFk}.{$params['fieldfk']}", 'ID');
        $db->select("{$aliasFromFk}.{$params['fieldshow']}", 'NOME');

        /* Verifica se existe algum campo concatenado */
        if (isset($params['tableconc']) and ! empty($params['tableconc'])) {
            $db->select("{$params['tableconc']}.{$params['fieldconc']}", 'CONCAT');
        }

        /* Define a ordem de exibição dos dados */
        
        if (isset($params['order']) and $params['order'] == 'ID') {
            $db->orderBy("ID ASC");
        } else {
            $db->orderBy("NOME ASC");
        }


        /* Define a tabela */
        $db->from("{$params['tablefk']}", $aliasFromFk, $schema);

        /* Monta a condição do SELECT em caso de dependência */
        if (!empty($arrayDepend)) {
            if (isset($params['dependfk']) and ! empty($params['dependfk'])) {
                $db->where("{$params['dependfk']} = '{$arrayDepend[$params['dependfk']]}'");
            }
            if (isset($params['where']) and ! empty($params['where'])) {
                foreach ($params['where'] as $keyWhere => $valueWhere) {
                    $db->where($this->prepareWherePhpTag($valueWhere));
                }
            }
        } else
        /* Monta a condição do SELECT em caso de populate */
        if (!empty($populateValues)) {
            if (isset($params['fieldpk']) and ! empty($params['fieldpk'])) {
                $db->where("{$params['fieldpk']} = '{$populateValues[$params['fieldpk']]}'");
            }
            if (isset($params['where']) and ! empty($params['where'])) {
                foreach ($params['where'] as $keyWhere => $valueWhere) {
                    $db->where($this->prepareWherePhpTag($valueWhere));
                }
            }
        } else {
            /* Define as condições do select caso houver */
            if (isset($params['where']) and ! empty($params['where'])) {
                foreach ($params['where'] as $keyWhere => $valueWhere) {
                    $db->where($this->prepareWherePhpTag($valueWhere));
                }
            }
        }

        /* Define o JOIN da tabela caso houver */
        if (isset($params['join']) and $params['join'] == "true") {
            for ($idxJoin = 0; $idxJoin < count($params['jointype']); $idxJoin++) {
                $joinAlias = (isset($params['joinalias'][$idxJoin]) and ! empty($params['joinalias'][$idxJoin])) ? $params['joinalias'][$idxJoin] : null;
                $db->join($params['joinfrom'][$idxJoin], $joinAlias, $params['joincond'][$idxJoin], $params['jointype'][$idxJoin], $params['joinschema'][$idxJoin]);
            }
        }

        if (isset($params['debug']) and $params['debug'] == "true") {
            $db->setDebug(true);
        }

        return $db->executeSelectQuery();
    }
}
