<?php
namespace Cityware\Form\Validators;

use Zend\Validator\AbstractValidator;

/**
 * Validador para fazer a validação de CPF (Cadastro de Pessoas Físicas)
 */
class EasyPassword extends AbstractValidator
{
    const INVALID_TOO_SHORT = 'i_short';
    const INVALID_TOO_LONG = 'i_long';
    const INVALID_ONE_NUMBER = 'i_number';
    const INVALID_ONE_LETTER = 'i_letter';
    const INVALID_ONE_LETTER_CAPS = 'i_lettercaps';
    const INVALID_ONE_SYMBOL = 'i_symbol';

    protected $messageTemplates = array(
        //self::INVALID_TOO_SHORT => "Password too short!",
        //self::INVALID_TOO_LONG => "Password too long!",
        //self::INVALID_ONE_NUMBER => "Password must include at least one number!",
        //self::INVALID_ONE_LETTER => "Password must include at least one letter!",
        //self::INVALID_ONE_LETTER_CAPS => "Password must include at least one CAPS!",
        //self::INVALID_ONE_SYMBOL => "Password must include at least one symbol!"
        self::INVALID_TOO_SHORT => "Senha muito curta! Deve ter no mínimo 6 caracteres.",
        self::INVALID_TOO_LONG => "Senha muito longa! Não deve ter mais de 20 caracteres",
        self::INVALID_ONE_NUMBER => "A senha deve conter pelo menos um número!",
        self::INVALID_ONE_LETTER => "A senha deve conter pelo menos uma letra!",
        self::INVALID_ONE_LETTER_CAPS => "A senha deve incluir pelo menos uma letra em maiusculo!",
        self::INVALID_ONE_SYMBOL => "A senha deve conter pelo menos um símbolo!"
    );
    private $skipFormat = false;

    /**
     * verifica se a senha é forte
     *
     * @param  string $value senha a ser validada
     * @return bool
     */
    public function isValid($value)
    {
        if (strlen($value) < 6) {
            $this->error(self::INVALID_TOO_SHORT, "Password too short!");

            return false;
        } elseif (strlen($value) > 20) {
            $this->error(self::INVALID_TOO_LONG, "Password too long!");

            return false;
        } elseif (!preg_match("#[0-9]+#", $value)) {
            $this->error(self::INVALID_ONE_NUMBER, "Password must include at least one number!");

            return false;
        } elseif (!preg_match("#[a-z]+#", $value)) {
            $this->error(self::INVALID_ONE_LETTER, "Password must include at least one letter!");

            return false;
        }

        return true;
    }
}
