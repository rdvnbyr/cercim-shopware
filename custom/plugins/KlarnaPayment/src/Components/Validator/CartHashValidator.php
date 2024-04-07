<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Validator;

use Symfony\Component\Validator\Constraints\EqualToValidator;

class CartHashValidator extends EqualToValidator
{
    /**
     * {@inheritdoc}
     */
    protected function getErrorCode(): ?string
    {
        return CartHash::NOT_EQUAL_ERROR;
    }
}
