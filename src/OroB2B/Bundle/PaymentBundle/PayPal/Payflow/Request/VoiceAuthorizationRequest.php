<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\Transaction;

class VoiceAuthorizationRequest extends AbstractRequest
{
    private function __construct()
    {
    }

    /** {@inheritdoc} */
    public function setOptions(array $options = [])
    {
        throw new \BadMethodCallException();
    }

    /** {@inheritdoc} */
    public function getAction()
    {
        return Transaction::VOICE_AUTHORIZATION;
    }
}
