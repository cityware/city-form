<?php
namespace Cityware\Form\Validators;

use Zend\Validator\AbstractValidator;

/**
 * Validador para fazer a validação de RENAVAM
 */
class Renavam extends AbstractValidator
{
    const INVALID_DIGITS = 'i_number';
    const INVALID_FORMAT = 'i_format';

    protected $messageTemplates = array(
        self::INVALID_DIGITS => "O renavam '%value%' não é válido",
        self::INVALID_FORMAT => "O formato do renavam '%value%' não é válido"
    );
    private $pattern = '/[0-9]{9}/i';
    private $skipFormat = false;

    /**
     * verifica se o CPF é válido
     *
     * @param  string $value cpf a ser validado
     * @return bool
     */
    public function isValid($value)
    {
        $this->setValue($value);

        if (!$this->skipFormat && preg_match($this->pattern, $value) == false) {
            $this->error(self::INVALID_FORMAT);

            return false;
        }

        $value = preg_replace('/[^\d]+/i', '', $value);

        $soma = 0;
        for ($i = 0; $i < 8; $i++) {
            $soma += substr($value, $i, 1) * ($i + 2);
        }

        $soma = $soma % 11;
        $ultimoDigito = ($soma == 10) ? 0 : $soma;
        $digitoInformado = substr($value, strlen($value) - 1, strlen($value));

        if ($ultimoDigito != $digitoInformado) {
            $this->error(self::INVALID_DIGITS);

            return false;
        }

        return true;
    }
}
