<?php
namespace Cityware\Form\Validators;

use Zend\Validator\AbstractValidator;

/**
 * Validador para fazer a validação de CPF (Cadastro de Pessoas Físicas)
 */
class Cpf extends AbstractValidator
{
    const INVALID_DIGITS = 'i_number';
    const INVALID_FORMAT = 'i_format';

    protected $messageTemplates = array(
        self::INVALID_DIGITS => "O CPF '%value%' não é válido",
        self::INVALID_FORMAT => "O formato do CPF '%value%' não é válido"
    );
    private $pattern = '/(\d{3})\.(\d{3})\.(\d{3})-(\d{2})/i';
    private $skipFormat = false;

    /**
     * verifica se o CPF é válido
     *
     * @param  string $value cpf a ser validado
     * @return bool
     */
    public function isValid($value)
    {
        //$value = preg_replace('/[^\d]+/i', '', $value);

        $this->setValue($value);

        if (!$this->skipFormat && preg_match($this->pattern, $value) == false) {
            $this->error(self::INVALID_FORMAT);

            return false;
        }

        $digits = preg_replace('/[^\d]+/i', '', $value);

        $padroesFalsos = Array(11111111111, 22222222222, 33333333333, 44444444444, 55555555555, 66666666666, 77777777777, 88888888888, 99999999999, 00000000000);

        if (in_array($digits, $padroesFalsos)) {
            $this->error(self::INVALID_DIGITS);

            return false;
        }

        $firstSum = 0;
        $secondSum = 0;

        for ($i = 0; $i < 9; $i++) {
            $firstSum += $digits{$i} * (10 - $i);
            $secondSum += $digits{$i} * (11 - $i);
        }

        $firstDigit = 11 - fmod($firstSum, 11);

        if ($firstDigit >= 10) {
            $firstDigit = 0;
        }

        $secondSum = $secondSum + ($firstDigit * 2);
        $secondDigit = 11 - fmod($secondSum, 11);

        if ($secondDigit >= 10) {
            $secondDigit = 0;
        }

        if (substr($digits, -2) != ($firstDigit . $secondDigit)) {
            $this->error(self::INVALID_DIGITS);

            return false;
        }

        return true;
    }
}
