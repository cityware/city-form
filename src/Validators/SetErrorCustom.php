<?php
namespace Cityware\Form\Validators;

use Zend\Validator\AbstractValidator;

/**
 * Validador para fazer a validação de CPF (Cadastro de Pessoas Físicas)
 */
class SetErrorCustom extends AbstractValidator
{
    /**
     * verifica se a senha é forte
     *
     * @param  string $value senha a ser validada
     * @return bool
     */
    public function isValid($value)
    {
        return false;
    }
}
