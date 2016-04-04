<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Client;

use Guzzle\Http\ClientInterface as HTTPClientInterface;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\NVP\EncoderInterface;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\ResponseInterface;

class NVPClient implements ClientInterface
{
    /** @var HTTPClientInterface */
    protected $httpClient;

    /** @var EncoderInterface */
    protected $encoder;

    /**
     * @param HTTPClientInterface $httpClient
     * @param EncoderInterface $encoder
     */
    public function __construct(HTTPClientInterface $httpClient, EncoderInterface $encoder)
    {
        $this->httpClient = $httpClient;
        $this->encoder = $encoder;
    }

    /**
     * @param array $options
     * @return ResponseInterface
     */
    public function send(array $options = [])
    {
        $response = $this->httpClient
            ->post(NVPClient::PILOT_HOST_ADDRESS, [], $this->encoder->encode($options))
            ->send();

        return $this->encoder->decode($response->getBody(true));
    }
}
