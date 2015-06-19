<?php

namespace Cityware\Form\Adapter;

use Zend\Config\Factory AS ZendConfigFile;
use Zend\I18n\Translator\Translator;
use Zend\Mvc\I18n\Translator as MvcTranslator;
use Zend\Form\Element as ZendFormElement;
use Zend\InputFilter\InputFilter as ZendInputFilter;
use Zend\InputFilter\Factory as ZendInputFilterFactory;
use Zend\Form\Form as ZendForm;
use Zend\Validator\ValidatorChain as ZendValidatorChain;
use Zend\Validator\AbstractValidator;
use Zend\Session\Container as SessionContainer;

class ZendAdapter extends ZendForm implements AdapterInterface {

    private $nameIniForm, $formDefaultConfig, $formFiledsConfig, $formButtonsConfig;
    private $urlAction, $translator, $moduleName, $controllerName, $actionName, $editFlag = false;
    private $aOptions = Array(), $aAttributes = Array(), $selectOptions = Array();
    private $aParams = Array(), $populateParams = Array(), $selectExternalOptions = Array();
    private static $aSession = Array();

    public function getEditFlag() {
        return $this->editFlag;
    }

    public function setEditFlag($editFlag) {
        $this->editFlag = $editFlag;
    }

    public function getPopulateParams() {
        return $this->populateParams;
    }

    public function setPopulateParams(array $populateParams = null) {
        $this->populateParams = $populateParams;

        return $this;
    }

    public function addPopulateParams(array $populateParams = null) {
        $this->populateParams += $populateParams;

        return $this;
    }

    public function getSelectExternalOptions() {
        return $this->selectExternalOptions;
    }

    public function setSelectExternalOptions(array $selectExternalOptions = Array()) {
        $this->selectExternalOptions = $selectExternalOptions;

        return $this;
    }

    public function getSelectOptions() {
        return $this->selectOptions;
    }

    public function setSelectOptions(array $selectOptions = Array()) {
        $this->selectOptions = $selectOptions;

        return $this;
    }

    public function addSelectOptions(array $selectOptions = Array()) {
        $this->selectOptions += $selectOptions;

        return $this;
    }

    public function setUrlParams(array $params = Array()) {
        $this->aParams = $params;
        $this->moduleName = $params['module'];
        $this->controllerName = $params['__CONTROLLER__'];
        $this->actionName = $params['action'];
        return $this;
    }

    public function addUrlParams(array $params = Array()) {
        $this->aParams += $params;

        return $this;
    }

    public function getUrlParams() {
        return $this->aParams;
    }

    public function getUrlParam($key) {
        return (isset($this->aParams[$key]) and ! empty($this->aParams[$key])) ? $this->aParams[$key] : null;
    }

    /**
     * Retorna o nome do arquivo de configuração do formulário
     * @return type
     */
    public function getNameIniForm() {
        if (!isset($this->nameIniForm) or empty($this->nameIniForm)) {
            if (trim(strtolower($this->actionName)) == 'edit') {
                $this->setNameIniForm('edit');
            } else {
                $this->setNameIniForm('add');
            }
        }

        return $this->nameIniForm;
    }

    /**
     * Define o nome do arquivo de configuração do formulário
     * @param  type                               $nameIniForm
     * @return \Cityware\Form\Adapter\ZendAdapter
     */
    public function setNameIniForm($nameIniForm) {
        $this->nameIniForm = $nameIniForm;

        return $this;
    }

    /**
     * Retorna dados do arquivo de configuração do formulário
     * @return array
     */
    public function getConfigForm() {
        $config = ZendConfigFile::fromFile(self::$aSession['moduleIni'] . $this->controllerName . DS . $this->getNameIniForm() . '.ini');
        $this->formDefaultConfig = $config['formconfig']['form'];
        $this->formFiledsConfig = $config['formfieldsconfig'];
        $this->formButtonsConfig = $config['formbuttonconfig'];

        return $config;
    }

    /**
     * Envia a URL de Action do formulário
     * @return string
     */
    public function getUrlAction() {
        if (!isset($this->urlAction) or empty($this->urlAction)) {
            if (trim(strtolower($this->actionName)) == 'edit') {
                $urlAction = LINK_DEFAULT . $this->moduleName . '/' . $this->controllerName . '/' . $this->actionName . '/id/' . $this->getUrlParam('id');
            } else {
                $urlAction = LINK_DEFAULT . $this->moduleName . '/' . $this->controllerName . '/' . $this->actionName;
            }
            $this->setUrlAction($urlAction);
        }

        return $this->urlAction;
    }

    /**
     * Define a URL de Action do formulário
     * @param  string                             $urlAction
     * @return \Cityware\Form\Adapter\ZendAdapter
     */
    public function setUrlAction($urlAction) {
        $this->urlAction = $urlAction;

        return $this;
    }

    /**
     * Função construtora da classe
     */
    public function __construct($name = null, $options = array()) {
        parent::__construct($name, $options);

        $sessionRoute = new SessionContainer('globalRoute');
        self::$aSession = $sessionRoute->getArrayCopy();
    }

    /**
     * Prepara as traduções do formulário
     * @return \Cityware\Form\Adapter\ZendAdapter
     */
    private function prepareTranslator() {

        //Create the translator
        $translator = new MvcTranslator(new Translator());

        //Add the translation file. Here we are using the Portuguese-Brazilian translation
        $translator->addTranslationFile('PhpArray', self::$aSession['moduleTranslate'] . $translator->getLocale() . DS . $this->controllerName . DS . $this->getNameIniForm() . '.php', 'default', $translator->getLocale());
        $translator->addTranslationFile('PhpArray', self::$aSession['moduleTranslate'] . $translator->getLocale() . DS . "Zend_Validate.php", 'default', $translator->getLocale());
        $translator->addTranslationFile('PhpArray', self::$aSession['moduleTranslate'] . $translator->getLocale() . DS . "Zend_Captcha.php", 'default', $translator->getLocale());

        //Set the default translator for validators
        AbstractValidator::setDefaultTranslator($translator);

        $this->translator = $translator;

        return $this;
    }

    /**
     * Função que pega a tradução do campo do formulário
     * @param  string $message
     * @return string
     */
    public function getTranslator($message) {
        return $this->translator->translate($message);
    }

    /**
     * Função que prepara a tag FORM com as propriedades de configuração
     * @return \Cityware\Form\Adapter\ZendAdapter
     */
    private function prepareTagForm() {
        $this->getConfigForm();

        /* Conbfigurações padrão do fomulário */
        //$this->setAttribute('role', 'form');
        $this->setAttribute('method', $this->formDefaultConfig['method']);
        $this->setAttribute('enctype', ((!empty($this->formDefaultConfig['enctype'])) ? $this->formDefaultConfig['enctype'] : 'multipart/form-data'));
        $this->setAttribute('action', $this->getUrlAction());

        /* Id do formulário */
        if (isset($this->formDefaultConfig['id']) and ! empty($this->formDefaultConfig['id'])) {
            $this->setAttribute('id', $this->formDefaultConfig['id']);
        } else {
            $this->setAttribute('id', 'formBy' . ucfirst($this->controllerName) . ucfirst($this->actionName));
        }

        $this->setAttribute('class', 'form-cms');

        return $this;
    }

    /**
     * Função de geração do formulário
     * @param  array                              $realTimeValidation
     * @return \Cityware\Form\Adapter\ZendAdapter
     */
    public function formBuider(array $dataValidation = null, array $realTimeValidation = null) {
        $this->prepareTranslator();
        $this->prepareTagForm();
        $this->setUseAsBaseFieldset(true);

        $mainFilter = new ZendInputFilter();

        /* Loop de preparação dos campos de acordo com a ordem do arquivo INI */
        foreach ($this->formFiledsConfig as $fieldName => $fieldParams) {

            $elementField = $this->prepareFields($fieldName, $fieldParams);

            if (isset($elementField['element']) and ! empty($elementField['element'])) {

                $this->aAttributes['id'] = $fieldName;

                /* Verifica se é um campo multi valores e adiciona os valores padrões */
                $element = $this->populateMultioptionsField($elementField['element'], $fieldName, $fieldParams);

                /* Define os atributos do campo */
                $element->setAttributes($this->aAttributes);

                /* Define os atributos do campo */
                $element->setOptions($this->aOptions);

                /* Adiciona o campo no formulário */
                $this->add($element);

                $this->selectOptions = Array();
                $this->aOptions = Array();
                $this->aAttributes = Array();

                //if (isset($fieldParams['validation']) or isset($fieldParams['validationtype'])) {
                /* Definições de validação dos campos de formulário */
                //    $inputFilterValidator = $this->formValidation($mainFilter, $fieldParams, $realTimeValidation);
                //} else {
                //    $fieldParams['validation'] = 'false';
                /* Definições de validação dos campos de formulário */
                $inputFilterValidator = $this->formValidation($mainFilter, $fieldParams, $realTimeValidation);
                //}
            }
        }

        if (isset($inputFilterValidator) and ! empty($inputFilterValidator)) {
            $this->setInputFilter($inputFilterValidator);
        }

        /* Loop de definição dos botões do formulário */
        foreach ($this->formButtonsConfig as $buttonName => $params) {
            $this->add($this->prepareButtons($buttonName, $params));
        }

        $this->preparePopulateForm();

        if (!empty($dataValidation)) {
            $this->setData($dataValidation);
        }

        return $this;
    }

    /**
     * Função de definições de validação dos campos do formulário
     * @param  object $mainFilter
     * @param  array  $params
     * @param  array  $validateRealTime
     * @return object
     */
    private function formValidation($mainFilter, array $params, array $validateRealTime = null) {

        /* Implementa validação pelo tipo definido */
        $mainFilter = $this->defineValidation($params, $mainFilter);

        /* Verifica se a validação será feita em modo realtime */
        if (is_array($validateRealTime) and ! empty($validateRealTime)) {
            foreach ($validateRealTime as $valueValRealTime) {
                if ($params['name'] == $valueValRealTime['name']) {
                    /* Implementa validação pelo tipo definido */
                    $mainFilter = $this->defineValidation($valueValRealTime, $mainFilter);
                }
            }
        }

        /* Verifica a validação de envio de arquivo caso o campo seja do tipo */
        if ((isset($params['validationfile']) and ! empty($params['validationfile'])) and ( strtolower($params['type']) == 'file' or strtolower($params['type']) == 'fileflash' or strtolower($params['type']) == 'fileimage')) {
            $mainFilter = $this->defineValidationFiles($params, $mainFilter);
        }

        return $mainFilter;
    }

    /**
     * Função que popula o formulário
     */
    private function preparePopulateForm() {
        $populateValues = array();

        if (trim(strtolower($this->actionName)) == 'edit' or $this->editFlag == true) {

            /* Instancia o Model de formulários */
            $moduleName = '\\' . ucfirst($this->moduleName) . '\\Models\\Forms';
            $relationship = new $moduleName();
            $relationship->setConfigForm($this->formDefaultConfig);

            /* Verifica se há dados externos se não popula com dados do banco */
            if (!empty($this->populateParams)) {
                $populateValues = $this->populateParams;
            } else {
                $populateValues = $relationship->populateForm($this->getUrlParam('id'));
            }

            foreach ($this->formFiledsConfig as $fieldName => $params) {
                if (isset($params['relationship']) and strtolower($params['relationship']) == 'true') {
                    if (strtolower($params['type']) == 'multiselect' and empty($populateValues)) {

                        /* Executa a query e retorna os dados do banco */
                        $data = $relationship->populateSelect($params, null, $populateValues);

                        foreach ($data as $iValues) {
                            $populateValues[$params['fieldfk']][] = $iValues['ID'];
                        }
                    }
                }

                if (strtolower($params['type']) == 'password') {
                    if (isset($params['encrypted']) and strtolower($params['encrypted']) == 'true') {
                        if (isset($populateValues[$fieldName]) and !empty($populateValues[$fieldName])) {
                            $crypt = new \Cityware\Security\Crypt();
                            $populateValues[$fieldName] = $crypt->decrypt($populateValues[$fieldName]);
                        }
                    }
                }

                if (isset($params['concatcolumn']) and ! empty($params['concatcolumn'])) {
                    if (isset($params['concattype']) and $params['concattype'] == 'inverse') {
                        $populateValues[$fieldName] = $populateValues[$params['concatcolumn']] . $params['concatseparator'] . $populateValues[$fieldName];
                    } else {
                        $populateValues[$fieldName] = $populateValues[$fieldName] . $params['concatseparator'] . $populateValues[$params['concatcolumn']];
                    }
                }

                if (strtolower($params['type']) == 'money') {
                    $populateValues[$fieldName] = \Cityware\Format\Money::formataValor($populateValues[$fieldName], '.', 2, ',', '.');
                }
            }

            $this->populateValues($populateValues);
            $populateValues = array();
        } else {
            /* Verifica se há dados externos e popula o formulário */
            if (!empty($this->populateParams)) {
                $populateValues = $this->populateParams;
            } else {
                $populateValues = array();
            }
            $this->populateValues($populateValues);
            $populateValues = array();
        }
    }

    /**
     * Verifica se é um campo multi valores e adiciona os valores padrões
     * @param  object $element
     * @param  string $fieldName
     * @param  array  $fieldParams
     * @return object
     */
    private function populateMultioptionsField($element, $fieldName, $fieldParams) {
        /* Verifica e define conteudo de campo relarionado */
        if (isset($fieldParams['relationship']) and strtolower($fieldParams['relationship']) == 'true') {

            /* Verifica se há valores de multi-dependência */
            if (isset($fieldParams['depend']) and strtolower($fieldParams['depend']) == 'true') {
                $this->selectOptions += $this->dependRelationshipFields($fieldParams);

                if (isset($this->selectExternalOptions[$fieldName]) and ! empty($this->selectExternalOptions[$fieldName])) {
                    $this->selectOptions += $this->selectExternalOptions[$fieldName];
                }

                $element->setValueOptions($this->selectOptions);
            } else {
                $this->selectOptions += $this->relationshipFields($fieldParams);
                $element->setValueOptions($this->selectOptions);
            }
        } else {
            /* Verifica se há valores pre-definidos e adiciona */
            if (isset($fieldParams['values']) and ! empty($fieldParams['values'])) {
                $values = explode(",", $fieldParams['values']);
                $valueDisplay = explode(",", $this->getTranslator($fieldName . '_values'));
                $multiOptions = array();
                for ($iValues = 0; $iValues < count($values); $iValues++) {
                    $multiOptions[$values[$iValues]] = trim($valueDisplay[$iValues]);
                }
                $this->selectOptions += $multiOptions;

                if (isset($this->selectExternalOptions[$fieldName]) and ! empty($this->selectExternalOptions[$fieldName])) {
                    $this->selectOptions += $this->selectExternalOptions[$fieldName];
                }

                $element->setValueOptions($this->selectOptions);
            } elseif (isset($fieldParams['type']) and ( $fieldParams['type'] == 'select' or $fieldParams['type'] == 'multicheckbox')) {
                /* Verifica se é um campo multi valores e adiciona os valores padrões */

                if (isset($this->selectExternalOptions[$fieldName]) and ! empty($this->selectExternalOptions[$fieldName])) {
                    $this->selectOptions += $this->selectExternalOptions[$fieldName];
                }

                $element->setValueOptions($this->selectOptions);
            }
        }

        return $element;
    }

    /**
     * Função que define dados relacionado em algum campo do formulário
     * @param type $params
     */
    private function relationshipFields($params) {

        /* Pega dados do banco */
        $moduleName = '\\' . ucfirst($this->moduleName) . '\\Models\\Forms';
        $relationship = new $moduleName();
        $data = $relationship->populateSelect($params);

        /* Formata os dados do banco para o padrão do zend_form */
        $multiOptions = Array();
        for ($iValues = 0; $iValues < count($data); $iValues++) {
            if (isset($params['fieldconc']) && !empty($params['fieldconc'])) {
                $multiOptions[$data[$iValues]['ID']] = trim($data[$iValues]['CONCAT'] . ' - ' . $data[$iValues]['NOME']);
            } else {
                $multiOptions[$data[$iValues]['ID']] = trim($data[$iValues]['NOME']);
            }
        }

        /* Verifica se há valores pre-definidos e adiciona */
        if (isset($params['values'])) {
            $values = explode(",", $params['values']);
            $valueDisplay = explode(",", $this->translate[$params['name'] . '_values']);
            for ($iValues = 0; $iValues < count($values); $iValues++) {
                $multiOptions[] = trim($valueDisplay[$iValues]);
            }
        }

        return $multiOptions;
    }

    /**
     * Função que define dados relacionado de dependência em algum campo do formulário
     * @param  type $params
     * @param  type $populate
     * @return type
     */
    private function dependRelationshipFields($params) {
        $multiOptions = Array();
        $moduleName = '\\' . ucfirst($this->moduleName) . '\\Models\\Forms';
        $relationship = new $moduleName();

        /* Define o schema da tabela se definido */
        if (trim(strtolower($this->actionName)) == 'edit') {
            if (!empty($this->populateParams)) {
                $arrayDepend = $this->populateParams;
            } else {
                $relationship->setConfigForm($this->formDefaultConfig);
                $arrayDepend = $relationship->populateForm($this->getUrlParam('id'));
            }
        } else {
            if (!empty($this->populateParams)) {
                $arrayDepend = $this->populateParams;
            }
        }

        if (!empty($arrayDepend[$params['dependfk']])) {

            /* Executa a query e retorna os dados do banco */
            $data = $relationship->populateSelect($params, $arrayDepend);

            /* Formata os dados do banco para o padrão do zend_form */
            for ($iValues = 0; $iValues < count($data); $iValues++) {
                $multiOptions[$data[$iValues]['ID']] = trim($data[$iValues]['NOME']);
            }
        }

        /* Verifica se há valores pre-definidos e adiciona */
        if (isset($params['values'])) {
            $values = explode(",", $params['values']);
            $valueDisplay = explode(",", $this->translate[$params['name'] . '_values']);
            for ($iValues = 0; $iValues < count($values); $iValues++) {
                $multiOptions[] = trim($valueDisplay[$iValues]);
            }
        }

        return $multiOptions;
    }

    /**
     * Função que prepara os botões do formulário
     * @param  type                      $buttonName
     * @param  array                     $params
     * @return \Zend\Form\Element\Button
     */
    private function prepareButtons($buttonName, array $params = Array()) {
        /* Preparação dos botões */

        $class = "btn ";

        $this->aOptions = Array();
        if (strtolower($params['type']) == 'submit') {
            //$element = new ZendFormElement\Submit($buttonName);
            $element = new ZendFormElement\Button($buttonName);
            $this->aAttributes['type'] = 'submit';
            //$class .= ' btn-success ';
        } elseif (strtolower($params['type']) == 'button') {
            $element = new ZendFormElement\Button($buttonName);
            //$class .= ' btn_default ';
        }

        if (isset($params['class']) and ! empty($params['class'])) {
            $this->aAttributes['class'] = $class . $params['class'];
        }
        $this->aOptions['column-size'] = 'sm-6 col-sm-offset-2';
        $this->aOptions['twb-layout'] = 'inline';

        $element->setValue($this->getTranslator('btn_' . $buttonName));

        $element->setLabel($this->getTranslator('btn_' . $buttonName));
        $element->setAttributes($this->aAttributes);
        $element->setOptions($this->aOptions);

        return $element;
    }

    /**
     * Prepara os campos do formulário
     * @param  string    $fieldName
     * @param  array     $fieldParams
     * @param  array     $options
     * @return object
     * @throws Exception
     */
    private function prepareFields($fieldName, array $fieldParams, array $options = array()) {
        $element = null;
        $extraLabel = " ";
        $this->selectOptions = Array();
        $this->aOptions = Array();
        $this->aAttributes = Array();

        /* Define o tooltip do campo */
        $tooltip = (isset($fieldParams['tooltip']) and $fieldParams['tooltip'] == 'true') ? "<a class=\"tooltip-marc\" href=\"#\" data-toggle=\"tooltip\" title=\"{$this->getTranslator($fieldName . '_tooltip')}\">[?]</a>" : null;
        $this->aOptions['tooltip'] = $tooltip;

        /* Define como será mostrado o nome do campo (se é obrigatório ou não) */
        if (strtolower($fieldParams['type']) != 'hidden') {
            if (isset($fieldParams['validation']) and stristr(strtolower($fieldParams['validation']), "required")) {
                $extraLabel = " * ";
            }
        }

        switch (strtolower($fieldParams['type'])) {
            /* Caso hidden */
            case 'primary':
            case 'hidden':
                $element = new ZendFormElement\Hidden($fieldName);
                break;

            /* Caso Csrf */
            case 'csrf':
            case 'sec':
                $element = new ZendFormElement\Csrf($fieldName);
                $element->setCsrfValidatorOptions(Array('timeout' => '600'));
                break;

            /* Caso text */
            case 'text':
                $element = new ZendFormElement\Text($fieldName);

                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);
                $this->aAttributes['class'] = 'form-input';
                break;

            /* Caso textarea */
            case 'textarea':
                $element = new ZendFormElement\Textarea($fieldName);
                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);

                /* Define os padrões de colunas e linhas do campo */
                $this->aAttributes['rows'] = (isset($fieldParams['rows']) and ! empty($fieldParams['rows'])) ? $fieldParams['rows'] : 5;
                $this->aAttributes['cols'] = (isset($fieldParams['cols']) and ! empty($fieldParams['cols'])) ? $fieldParams['cols'] : 10;
                $this->aAttributes['class'] = 'form-input';
                $this->aAttributes['data-editor'] = 'false';

                break;

            /* Caso editor */
            case 'editor':
                $element = new ZendFormElement\Textarea($fieldName);
                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);

                /* Define os padrões de colunas e linhas do campo */
                $this->aAttributes['rows'] = (isset($fieldParams['rows']) and ! empty($fieldParams['rows'])) ? $fieldParams['rows'] : 5;
                $this->aAttributes['cols'] = (isset($fieldParams['cols']) and ! empty($fieldParams['cols'])) ? $fieldParams['cols'] : 10;

                /* Verifica se utilizará o editor */
                $this->aAttributes['class'] = 'editorw';

                $this->aAttributes['data-editor'] = 'true';

                /* Verifica a pasta de upload */
                if (isset($this->formDefaultConfig['destination']) and ! empty($this->formDefaultConfig['destination'])) {

                    /* Caso não exista a pasta cria o mesmo */
                    if (!is_dir(UPLOAD_PATH . $this->formDefaultConfig['destination'])) {
                        mkdir(UPLOAD_PATH . $this->formDefaultConfig['destination'], 0777, true);
                        chmod(UPLOAD_PATH . $this->formDefaultConfig['destination'], 0777);
                    }
                    $_SESSION['KCFINDER'] = array();
                    $_SESSION['KCFINDER']['disabled'] = false;
                    $_SESSION['KCFINDER']['uploadURL'] = URL_UPLOAD . $this->formDefaultConfig['destination'];
                    $_SESSION['KCFINDER']['uploadDir'] = UPLOAD_PATH . $this->formDefaultConfig['destination'];
                } else {
                    throw new \Exception('Defina a pasta de destino de upload das imagens do editor!', 500);
                }
                break;

            /* Caso password */
            case 'password':
                $element = new ZendFormElement\Password($fieldName);
                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);
                $this->aAttributes['renderPassword'] = true;
                $this->aAttributes['class'] = 'form-input';
                break;

            /* Caso radio */
            case 'radio':
                $element = new ZendFormElement\Radio($fieldName);
                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);
                $this->aAttributes['class'] = 'radio';
                break;

            /* Caso checkbox */
            case 'checkbox':
                $element = new ZendFormElement\Checkbox($fieldName);
                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);
                $element->setUncheckedValue(null);
                $this->aAttributes['class'] = 'checkbox';
                break;

            /* Caso multicheckbox */
            case 'multicheckbox':
                $element = new ZendFormElement\MultiCheckbox($fieldName);
                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);
                $element->setUncheckedValue(null);
                break;

            /* Caso select */
            case 'select':
                $element = new ZendFormElement\Select($fieldName, Array('disable_inarray_validator' => true));
                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);

                if (isset($fieldParams['placeholder']) and strtolower($fieldParams['placeholder']) == 'true') {
                    $this->selectOptions[''] = $this->getTranslator($fieldName . '_placeholder');
                } else {
                    $this->selectOptions[''] = "---------";
                }

                $this->aAttributes['class'] = 'form-input-select';
                break;

            /* Caso multiselect */
            case 'multiselect':
                $element = new ZendFormElement\Select($fieldName);
                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);
                $this->aAttributes['class'] = 'form-input-select ms';
                $this->aAttributes['multiple'] = 'multiple';
                break;

            /* Caso fileimage */
            case 'fileimage':
                $element = new \Cityware\Form\Element\Fileimage($fieldName);
                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);
                //$this->aAttributes['multiple'] = true;
                $this->aAttributes['class'] = 'hiddenImageFile';

                /* Verifica a pasta de upload */
                if (isset($this->formDefaultConfig['pathfiles']) and ! empty($this->formDefaultConfig['pathfiles'])) {

                    /* Caso não exista a pasta cria o mesmo */
                    if (!is_dir(UPLOAD_PATH . $this->formDefaultConfig['pathfiles'])) {
                        mkdir(UPLOAD_PATH . $this->formDefaultConfig['pathfiles'], 0777, true);
                        chmod(UPLOAD_PATH . $this->formDefaultConfig['pathfiles'], 0777);
                    }
                    $this->aAttributes['data-path'] = LINK_DEFAULT . 'uploads/' . $this->formDefaultConfig['pathfiles'];
                } else {
                    throw new \Exception('Defina a pasta de destino de upload das imagens do editor!', 500);
                }

                break;

            /* Caso file */
            case 'file':
                $element = new ZendFormElement\File($fieldName);
                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);
                break;

            case 'money':
                $element = new ZendFormElement\Text($fieldName);
                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);
                $this->aAttributes['class'] = 'form-input';
                break;

            /* HTML5 Elements */

            /* Caso url */
            case 'url':
                $element = new ZendFormElement\Url($fieldName);
                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);
                $this->aAttributes['class'] = 'form-input';
                break;

            /* Caso date */
            case 'date':
                $element = new ZendFormElement\Date($fieldName);
                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);
                $this->aAttributes['min'] = (date("Y") - 10) . '-01-01';
                $this->aAttributes['max'] = (date("Y") + 10) . '-12-31';
                $this->aAttributes['class'] = 'form-input';
                break;

            case 'dateage':
                $element = new ZendFormElement\Date($fieldName);
                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);
                $this->aAttributes['min'] = (date("Y") - 100) . '-01-01';
                $this->aAttributes['max'] = (date("Y") + 100) . '-12-31';
                $this->aAttributes['class'] = 'form-input';
                break;

            /* Caso time */
            case 'time':
                $element = new ZendFormElement\DateTime($fieldName);
                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);
                $this->aAttributes['class'] = 'form-input';
                $this->aAttributes['min'] = '00:00:00';
                $this->aAttributes['max'] = '23:59:59';
                $this->aOptions['format'] = 'H:i:s';
                break;

            /* Caso date */
            case 'datetime':
                $element = new ZendFormElement\DateTime($fieldName);
                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);
                $this->aAttributes['min'] = (date("Y") - 10) . '-01-01 00:00:00';
                $this->aAttributes['max'] = (date("Y") + 10) . '-12-31 23:59:59';
                $this->aAttributes['class'] = 'form-input';
                break;

            /* Caso email */
            case 'email':
                $element = new ZendFormElement\Email($fieldName);
                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);
                $this->aAttributes['class'] = 'form-input';
                break;

            /* Caso number */
            case 'number':
                $element = new ZendFormElement\Number($fieldName);
                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);
                $this->aAttributes['step'] = '1';
                $this->aAttributes['class'] = 'form-input';
                break;

            case 'integer':
                $element = new ZendFormElement\Number($fieldName);
                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);
                $this->aAttributes['min'] = '0';
                $this->aAttributes['max'] = '99999999999999999999';
                $this->aAttributes['step'] = '1';
                $this->aAttributes['class'] = 'form-input';
                break;

            case 'float':
                $element = new ZendFormElement\Number($fieldName);
                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);
                $this->aAttributes['step'] = '0.001';
                $this->aAttributes['class'] = 'form-input';
                break;

            /* Plataforma */

            /* Caso select */
            case 'status':
                $element = new ZendFormElement\Select($fieldName, Array('disable_inarray_validator' => true));
                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);
                if (isset($fieldParams['placeholder']) and strtolower($fieldParams['placeholder']) == 'true') {
                    $this->selectOptions[''] = $this->getTranslator($fieldName . '_placeholder');
                } else {
                    $this->selectOptions[''] = "---------";
                }
                $this->aAttributes['class'] = 'form-input-select';
                break;

            /* Caso boolean */
            case 'boolean':
                $element = new ZendFormElement\Select($fieldName, Array('disable_inarray_validator' => true));
                $element->setLabel($this->getTranslator($fieldName) . $extraLabel);
                if (isset($fieldParams['placeholder']) and strtolower($fieldParams['placeholder']) == 'true') {
                    $this->selectOptions[''] = $this->getTranslator($fieldName . '_placeholder');
                } else {
                    $this->selectOptions[''] = "---------";
                }
                $this->aAttributes['class'] = 'form-input-select';
                break;
        }

        /* Verifica se foi setado classe de estilo e implementa */
        if (isset($fieldParams['class']) and ! empty($fieldParams['class'])) {
            if (isset($this->aAttributes['class']) and $this->aAttributes['class'] != "") {
                $this->aAttributes['class'] = $this->aAttributes['class'] . " " . $fieldParams['class'];
            } else {
                $this->aAttributes['class'] = $fieldParams['class'];
            }
        }

        /* Define a descrição abaixo do campo */
        if (isset($fieldParams['description']) and $fieldParams['description'] == 'true') {
            $this->aOptions['help-block'] = $this->getTranslator($fieldName . '_description');
        }

        /* Verifica se foi setado grupo do campo e implementa */
        if (isset($fieldParams['group']) and ! empty($fieldParams['group'])) {
            $this->aOptions['group'] = $fieldParams['group'];
        }

        /* Verifica se foi setado placeholder no campo e implementa */
        if (isset($fieldParams['placeholder']) and strtolower($fieldParams['placeholder']) == 'true') {
            $this->aAttributes['placeholder'] = $this->getTranslator($fieldName . '_placeholder');
        }

        /* Verifica se foi setado somente leitura e implementa */
        if (isset($fieldParams['readonly']) and strtolower($fieldParams['readonly']) == 'true') {
            $this->aAttributes['readonly'] = 'readonly';
        }

        /* Verifica se foi setado desabilitado e implementa */
        if (isset($fieldParams['disabled']) and strtolower($fieldParams['disabled']) == 'true') {
            $this->aAttributes['disabled'] = true;
        }

        /* Verifica se utilizará mascara no campo */
        if (isset($fieldParams['mask']) and ! empty($fieldParams['mask'])) {
            $this->aAttributes['data-inputmask'] = $fieldParams['mask'];
        }

        /* Verifica se foi setado inputgroup tipo append e implementa */
        if (isset($fieldParams['groupappend']) and ! empty($fieldParams['groupappend'])) {
            $this->aOptions['add-on-append'] = $fieldParams['groupappend'];
        }

        /* Verifica se foi setado inputgroup tipo prepend e implementa */
        if (isset($fieldParams['groupprepend']) and ! empty($fieldParams['groupprepend'])) {
            $this->aOptions['add-on-prepend'] = $fieldParams['groupprepend'];
        }

        /* Verifica se foi setado como array e implementa */
        if (isset($fieldParams['array']) and strtolower($fieldParams['array']) == 'true') {
            $this->aOptions['disable_inarray_validator'] = false;
        }

        if (strtolower($fieldParams['type']) !== 'checkbox' and strtolower($fieldParams['type']) !== 'button') {
            if (strtolower($fieldParams['type']) !== 'textarea' and strtolower($fieldParams['type']) !== 'editor') {
                //$this->aOptions['column-size'] = 'col4';
            } else {
                //$this->aOptions['column-size'] = 'col6';
            }
            $this->aOptions['labelattributes'] = array('class' => 'form-label');
        } else {
            //$this->aOptions['column-size'] = 'col6 col-sm-offset-2';
            unset($this->aOptions['labelattributes']);
        }

        return Array('element' => $element, 'params' => $fieldParams);
    }

    /**
     * Função de definição de validações de campos
     * @param  array  $params
     * @param  object $mainFilter
     * @return object
     */
    private function defineValidation(array $params, $mainFilter) {
        $factory = new ZendInputFilterFactory();
        $validatorChain = new ZendValidatorChain();

        $validatorsField = Array();

        $iCount = 0;
        if (isset($params['validation']) and strtolower(trim($params['validation'])) == 'required') {
            $validatorsField['name'] = $params['name'];
            $validatorsField['required'] = true;
            $validatorsField['validators'][$iCount] = Array('name' => 'NotEmpty');
            $validatorsField['validators'][$iCount]['options']['messages'] = Array(
                \Zend\Validator\NotEmpty::IS_EMPTY => 'Campo de preenchimento obrigatório'
            );
            $iCount++;
        } else {
            $validatorsField['name'] = $params['name'];
            $validatorsField['required'] = false;
        }
        if (isset($params['validationtype'])) {

            /* Verifica o tipo de validação utilizada e formata para continuar */
            if (!is_array($params['validationtype'])) {
                $params['validationtype'] = array($params['validationtype']);
            }

            /* Procura o tipo de validação e define o mesmo */
            foreach ($params['validationtype'] as $value) {
                $options = Array();

                //$options['translator'] = $this->translator;

                switch (strtolower($value)) {
                    /* Zend Validator */
                    case 'alphanum':
                        if (isset($params['permiteespaco']) and ! empty($params['permiteespaco'])) {
                            $validatorsField['validators'][$iCount] = Array(
                                'name' => 'Alnum',
                                'options' => array($options, 'allowWhiteSpace' => true)
                            );
                        } else {
                            $validatorsField['validators'][$iCount] = Array('name' => 'Alnum');
                        }
                        break;
                    case 'alpha':
                        if (isset($params['permiteespaco']) and ! empty($params['permiteespaco'])) {
                            $validatorsField['validators'][$iCount] = Array(
                                'name' => 'Alpha',
                                'options' => array($options, 'allowWhiteSpace' => true)
                            );
                        } else {
                            $validatorsField['validators'][$iCount] = Array('name' => 'Alpha');
                        }
                        break;
                    case 'barcode':
                        $validatorsField['validators'][$iCount] = Array(
                            'name' => 'Barcode',
                            'options' => array($options, 'adapter' => $params['barcodeadapter'])
                        );
                        break;
                    case 'between':

                        if (isset($params['validationmax']) and ! empty($params['validationmax'])) {
                            $options['max'] = $params['validationmax'];
                        }
                        if (isset($params['validationmin']) and ! empty($params['validationmin'])) {
                            $options['min'] = $params['validationmin'];
                        }
                        $options['inclusive'] = true;

                        $validatorsField['validators'][$iCount] = Array(
                            'name' => 'Between',
                            'options' => $options
                        );
                        break;
                    case 'callback':
                        $validatorsField['validators'][$iCount] = Array(
                            'name' => 'Callback',
                            'options' => $options
                        );
                        break;
                    case 'creditcard':
                        $validatorsField['validators'][$iCount] = Array(
                            'name' => 'CreditCard',
                            'options' => $options
                        );
                        break;
                    case 'date':
                        $options['format'] = 'Y-m-d';
                        $validatorsField['validators'][$iCount] = Array(
                            'name' => 'Date',
                            'options' => $options
                        );
                        break;
                    case 'time':
                        $options['format'] = 'H:i:s';
                        $validatorsField['validators'][$iCount] = Array(
                            'name' => 'Date',
                            'options' => $options
                        );

                        break;
                    case 'datetime':
                        $validatorObj = new \Zend\I18n\Validator\DateTime();
                        $validatorObj->setDateType(\IntlDateFormatter::SHORT);
                        $validatorObj->setTimeType(\IntlDateFormatter::SHORT);
                        $validatorsField['validators'][$iCount] = $validatorChain->attach($validatorObj);
                        break;
                    case 'recordexists':

                        $options['adapter'] = \Cityware\Db\Factory::factory('zend')->getAdapter();
                        $options['field'] = $params['name'];

                        if (isset($params['recordTable']) and ! empty($params['recordTable'])) {
                            $options['table'] = $params['recordTable'];
                        } else {
                            $options['table'] = $this->formDefaultConfig['table'];
                        }
                        if (isset($params['recordSchema']) and ! empty($params['recordSchema'])) {
                            $options['schema'] = $params['recordSchema'];
                        } else {
                            $options['schema'] = $this->formDefaultConfig['schema'];
                        }

                        if (isset($params['exclude']) and $params['exclude'] == 'true') {
                            if (isset($params['excludeCol']) and ! empty($params['excludeCol'])) {
                                $options['exclude']['field'] = $params['excludeCol'];
                            } else {
                                throw new \Exception('Não foi definido nenhuma coluna de exclusão!', 500);
                            }

                            if (isset($params['excludeColValue']) and ! empty($params['excludeColValue'])) {
                                $options['exclude']['value'] = \Cityware\Format\Str::preparePhpTag($params['excludeColValue'], false);
                            } else if (isset($params['excludeUrlParam']) and ! empty($params['excludeUrlParam'])) {
                                $options['exclude']['value'] = $this->getUrlParam($params['excludeCol']);
                            } else {
                                throw new \Exception('Não foi definido nenhum valor de exclusão!', 500);
                            }
                        }

                        $validatorsField['validators'][$iCount] = Array(
                            'name' => 'Db\RecordExists',
                            'options' => $options
                        );
                        break;

                    case 'norecordexists':
                        $options['adapter'] = \Cityware\Db\Factory::factory('zend')->getAdapter();
                        $options['field'] = $params['name'];

                        if (isset($params['recordTable']) and ! empty($params['recordTable'])) {
                            $options['table'] = $params['recordTable'];
                        } else {
                            $options['table'] = $this->formDefaultConfig['table'];
                        }
                        if (isset($params['recordSchema']) and ! empty($params['recordSchema'])) {
                            $options['schema'] = $params['recordSchema'];
                        } else {
                            $options['schema'] = $this->formDefaultConfig['schema'];
                        }

                        if (isset($params['exclude']) and $params['exclude'] == 'true') {
                            if (isset($params['excludeCol']) and ! empty($params['excludeCol'])) {
                                $options['exclude']['field'] = $params['excludeCol'];
                            } else {
                                throw new \Exception('Não foi definido nenhuma coluna de exclusão!', 500);
                            }

                            if (isset($params['excludeColValue']) and ! empty($params['excludeColValue'])) {
                                $options['exclude']['value'] = \Cityware\Format\Str::preparePhpTag($params['excludeColValue'], false);
                            } else if (isset($params['excludeUrlParam']) and ! empty($params['excludeUrlParam'])) {
                                $options['exclude']['value'] = $this->getUrlParam($params['excludeCol']);
                            } else {
                                throw new \Exception('Não foi definido nenhum valor de exclusão!', 500);
                            }
                        }

                        $validatorsField['validators'][$iCount] = Array(
                            'name' => 'Db\NoRecordExists',
                            'options' => $options
                        );
                        break;
                    case 'digits':
                    case 'int':
                    case 'integer':
                        $validatorsField['validators'][$iCount] = Array(
                            'name' => 'Digits',
                            'options' => $options
                        );
                        break;
                    case 'email':
                        $validatorsField['validators'][$iCount] = Array(
                            'name' => 'EmailAddress',
                            'options' => array(
                                $options,
                                'allow' => \Zend\Validator\Hostname::ALLOW_DNS,
                                'mx' => false,
                                'domain' => true,
                            )
                        );
                        break;
                    case 'greaterthan':
                        if (isset($params['validationmin']) and ! empty($params['validationmin'])) {
                            $options['min'] = $params['validationmin'];
                        }
                        $validatorsField['validators'][$iCount] = Array(
                            'name' => 'GreaterThan',
                            'options' => $options
                        );
                        break;
                    case 'hex':
                        $validatorsField['validators'][$iCount] = Array(
                            'name' => 'Hex',
                            'options' => $options
                        );
                        break;
                    case 'hostname':
                        $validatorsField['validators'][$iCount] = Array(
                            'name' => 'Hostname',
                            'options' => $options
                        );
                        break;
                    case 'iban':
                        $validatorsField['validators'][$iCount] = Array(
                            'name' => 'Iban',
                            'options' => $options
                        );
                        break;
                    case 'identical':
                        $validatorsField['validators'][$iCount] = Array(
                            'name' => 'Identical',
                            'options' => array($options, 'token' => $params['validationcompare'])
                        );
                        break;
                    case 'ip':
                        $validatorsField['validators'][$iCount] = Array(
                            'name' => 'Ip',
                            'options' => $options
                        );
                        break;
                    case 'isbn':
                        $validatorsField['validators'][$iCount] = Array(
                            'name' => 'Isbn',
                            'options' => $options
                        );
                        break;
                    case 'postcode':
                        $validatorsField['validators'][$iCount] = Array(
                            'name' => 'PostCode',
                            'options' => $options
                        );
                        break;
                    case 'lessthan':
                        if (isset($params['validationmax']) and ! empty($params['validationmax'])) {
                            $options['max'] = $params['validationmax'];
                        }

                        $validatorsField['validators'][$iCount] = Array(
                            'name' => 'LessThan',
                            'options' => $options
                        );
                        break;
                    case 'regex':
                        $validatorsField['validators'][$iCount] = Array(
                            'name' => 'Regex',
                            'options' => Array($options, 'pattern' => $params['regexrule'])
                        );
                        break;
                    case 'stringlength':
                        if (isset($params['validationmax']) and ! empty($params['validationmax'])) {
                            $options['max'] = $params['validationmax'];
                        }
                        if (isset($params['validationmin']) and ! empty($params['validationmin'])) {
                            $options['min'] = $params['validationmin'];
                        }
                        $validatorsField['validators'][$iCount] = Array(
                            'name' => 'StringLength',
                            'options' => $options
                        );
                        break;

                    /* Customizados */
                    case 'custom':
                        $validatorsField['validators'][$iCount] = $validatorChain->attach(new $params['customclass']);
                        break;
                    case 'cpf':
                        $validatorsField['validators'][$iCount] = $validatorChain->attach(new \Cityware\Form\Validators\Cpf());
                        break;
                    case 'cnpj':
                        $validatorsField['validators'][$iCount] = $validatorChain->attach(new \Cityware\Form\Validators\Cnpj());
                        break;
                    case 'renavam':
                        $validatorsField['validators'][$iCount] = $validatorChain->attach(new \Cityware\Form\Validators\Renavam());
                        break;
                    case 'rg':
                        $validatorsField['validators'][$iCount] = $validatorChain->attach(new \Cityware\Form\Validators\Rg());
                        break;
                    case 'strongpassword':
                        $validatorsField['validators'][$iCount] = $validatorChain->attach(new \Cityware\Form\Validators\StrongPassword());
                        break;
                    case 'mediumpassword':
                        $validatorsField['validators'][$iCount] = $validatorChain->attach(new \Cityware\Form\Validators\MediumPassword());
                        break;
                    case 'easypassword':
                        $validatorsField['validators'][$iCount] = $validatorChain->attach(new \Cityware\Form\Validators\EasyPassword());
                        break;
                    case 'float':
                        $validatorsField['validators'][$iCount] = $validatorChain->attach(new \Zend\I18n\Validator\Float());
                        break;
                    case 'seterrorcustom':
                        $validatorsField['validators'][$iCount] = $validatorChain->attach(new \Cityware\Form\Validators\SetErrorCustom());
                        break;
                }

                if (isset($params['messages']) and ! empty($params['messages'])) {
                    $validatorsField['validators'][$iCount]['options']['messages'] = $params['messages'];
                }
                $iCount++;
            }
        }

        $mainFilter->add($factory->createInput($validatorsField));

        return $mainFilter;
    }

}
