<?php
namespace Cityware\Form\Validators;

use Zend\Validator\AbstractValidator;

/**
 * Validador para fazer a validação de RG (Registro Geral)
 */
class Rg extends AbstractValidator
{
    const INVALID_DIGITS = 'i_number';
    const INVALID_FORMAT = 'i_format';

    protected $messageTemplates = array(
        self::INVALID_DIGITS => "O RG '%value%' não é válido",
        self::INVALID_FORMAT => "O formato do RG '%value%' não é válido"
    );
    private $pattern = '/[0-9]{8}/i';
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
        $tamanho = strlen($value);
        $vetor = Array();

        if ($tamanho >= 1) {
            $vetor[0] = $value[0] * 2;
        }
        if ($tamanho >= 2) {
            $vetor[1] = $value[1] * 3;
        }
        if ($tamanho >= 3) {
            $vetor[2] = $value[2] * 4;
        }
        if ($tamanho >= 4) {
            $vetor[3] = $value[3] * 5;
        }
        if ($tamanho >= 5) {
            $vetor[4] = $value[4] * 6;
        }
        if ($tamanho >= 6) {
            $vetor[5] = $value[5] * 7;
        }
        if ($tamanho >= 7) {
            $vetor[6] = $value[6] * 8;
        }
        if ($tamanho >= 8) {
            $vetor[7] = $value[7] * 9;
        }
        if ($tamanho >= 9) {
            $vetor[8] = $value[8] * 100;
        }

        $total = 0;

        if ($tamanho >= 1) {
            $total += $vetor[0];
        }
        if ($tamanho >= 2) {
            $total += $vetor[1];
        }
        if ($tamanho >= 3) {
            $total += $vetor[2];
        }
        if ($tamanho >= 4) {
            $total += $vetor[3];
        }
        if ($tamanho >= 5) {
            $total += $vetor[4];
        }
        if ($tamanho >= 6) {
            $total += $vetor[5];
        }
        if ($tamanho >= 7) {
            $total += $vetor[6];
        }
        if ($tamanho >= 8) {
            $total += $vetor[7];
        }
        if ($tamanho >= 9) {
            $total += $vetor[8];
        }

        $resto = $total % 11;
        if ($resto != 0) {
            $this->error(self::INVALID_DIGITS);

            return false;
        }

        return true;
    }
}
