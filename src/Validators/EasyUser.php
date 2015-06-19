<?php

namespace Cityware\Form\Validators;

use Zend\Validator\AbstractValidator;

/**
 * Validador para fazer a validação de CPF (Cadastro de Pessoas Físicas)
 */
class EasyUser extends AbstractValidator
{
    const INVALID_TOO_SHORT = 'i_short';
    const INVALID_TOO_LONG = 'i_long';
    const INVALID_ONE_NUMBER = 'i_number';
    const INVALID_ONE_LETTER = 'i_letter';
    const INVALID_ONE_LETTER_CAPS = 'i_lettercaps';
    const INVALID_ONE_SYMBOL = 'i_symbol';

    protected $messageTemplates = array(
        self::INVALID_TOO_SHORT => "Usuário muito curto! Deve ter no mínimo 6 caracteres.",
        self::INVALID_TOO_LONG => "Usuário muito longo! Não deve ter mais de 20 caracteres",
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
            $this->_error(self::INVALID_TOO_SHORT, "Password too short!");

            return false;
        } elseif (strlen($value) > 100) {
            $this->_error(self::INVALID_TOO_LONG, "Password too long!");

            return false;
        }

        return true;
    }

}
