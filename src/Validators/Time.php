<?php
namespace Cityware\Form\Validators;

use Zend\Validator\AbstractValidator;

/**
 * Validador para fazer a validação de CPF (Cadastro de Pessoas Físicas)
 */
class Time extends AbstractValidator
{
    const INVALID = 'timeInvalid';
    const INVALID_TIME = 'timeInvalidTime';
    const FALSEFORMAT = 'timeFalseFormat';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $messageTemplates = array(
        self::INVALID => "Invalid type given. String, integer, array or Zend_Date expected",
        self::INVALID_TIME => "'%value%' não é uma hora válida",
        self::FALSEFORMAT => "'%value%' does not fit the date format '%format%'",
    );

    /**
     * @var array
     */
    protected $messageVariables = array(
        'format' => '_format'
    );

    /**
     * Optional format
     *
     * @var string|null
     */
    protected $_format;

    /**
     * Optional locale
     *
     * @var string|Zend_Locale|null
     */
    protected $_locale;

    /**
     * Sets validator options
     *
     * @param  string|Zend_Config $options OPTIONAL
     * @return void
     */

    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if $value is a valid date of the format YYYY-MM-DD
     * If optional $format or $locale is set the date format is checked
     * according to Zend_Date, see Zend_Date::isDate()
     *
     * @param  string|array|Zend_Date $value
     * @return boolean
     */
    public function isValid($value)
    {
        $this->setValue($value);

        if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $value)) {
            $this->error(self::INVALID_TIME);
            $this->_format = null;

            return false;
        }

        return true;
    }
}
