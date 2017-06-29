<?php

namespace ProtectedShops\Validators;

use Plenty\Validation\Validator;

/**
 *  Validator Class
 */
class PsLegalTextValidator extends Validator
{
    protected function defineAttributes()
    {
        $this->addString('legalText', true);
    }
}