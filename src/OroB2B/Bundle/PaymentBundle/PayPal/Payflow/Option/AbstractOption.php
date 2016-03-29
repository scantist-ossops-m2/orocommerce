<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractOption implements OptionInterface
{
    const YES = 'Y';

    const TRUE = 'TRUE';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
    }
}
