<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class CreateSecureToken extends AbstractBooleanOption
{
    const CREATESECURETOKEN = 'CREATESECURETOKEN';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(CreateSecureToken::CREATESECURETOKEN)
            ->setNormalizer(
                CreateSecureToken::CREATESECURETOKEN,
                $this->getNormalizer(CreateSecureToken::YES, CreateSecureToken::NO)
            );
    }
}
