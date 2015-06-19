<?php
namespace Cityware\Form\Validators;

use Zend\Validator\AbstractValidator;

/**
 * Validador para fazer a validação de CNPJ (Cadastro Nacional da Pessoa Jurídica)
 */
class Cnpj extends AbstractValidator
{
    const NOT_DIGITS   = 'notDigits';
    const STRING_EMPTY = 'digitsStringEmpty';
    const INVALID      = 'digitsInvalid';

    const INVALID_DIGITS = 'i_number';

    const INVALID_FORMAT = 'i_format';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $messageTemplates = array(
        self::INVALID_DIGITS => "O CNPJ '%value%' não é válido",
        self::INVALID_FORMAT => "O formato do CNPJ '%value%' não é válido"
    );

    private $pattern = '/(\d{2})\.(\d{3})\.(\d{3})\/(\d{4})-(\d{2})/i';
    private $skipFormat = false;

    /**
     * verifica se o cnpj é válido
     *
     * @param  string $value cnpj a ser validado
     * @return bool
     */
    public function isValid($value)
    {
        $this->setValue($value);

        if (!$this->skipFormat && preg_match($this->pattern, $value) == false) {
            $this->error(self::INVALID_FORMAT);

            return false;
        }

        $digits = preg_replace('/[^\d]+/i', '', $value);

        $padroesFalsos = Array(11111111111111, 22222222222222, 33333333333333, 44444444444444, 55555555555555, 66666666666666, 77777777777777, 88888888888888, 99999999999999, 00000000000000);

        if (in_array($digits, $padroesFalsos)) {
            $this->error(self::INVALID_DIGITS);

            return false;
        }

        $firstSum = 0;
        $secondSum = 0;

        $firstSum += ( 5 * $digits{0}) + (4 * $digits{1}) + (3 * $digits{2}) + (2 * $digits{3});
        $firstSum += ( 9 * $digits{4}) + (8 * $digits{5}) + (7 * $digits{6}) + (6 * $digits{7});
        $firstSum += ( 5 * $digits{8}) + (4 * $digits{9}) + (3 * $digits{10}) + (2 * $digits{11});

        $firstDigit = 11 - fmod($firstSum, 11);

        if ($firstDigit >= 10) {
            $firstDigit = 0;
        }

        $secondSum += ( 6 * $digits{0}) + (5 * $digits{1}) + (4 * $digits{2}) + (3 * $digits{3});
        $secondSum += ( 2 * $digits{4}) + (9 * $digits{5}) + (8 * $digits{6}) + (7 * $digits{7});
        $secondSum += ( 6 * $digits{8}) + (5 * $digits{9}) + (4 * $digits{10}) + (3 * $digits{11});
        $secondSum += ( $firstDigit * 2);

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
