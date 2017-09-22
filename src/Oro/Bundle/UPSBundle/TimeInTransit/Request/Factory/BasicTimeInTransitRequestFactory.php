<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Request\Factory;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;

class BasicTimeInTransitRequestFactory implements TimeInTransitRequestFactoryInterface
{
    /**
     * @var TimeInTransitRequestBuilderFactoryInterface
     */
    private $timeInTransitRequestBuilderFactory;

    /**
     * @param TimeInTransitRequestBuilderFactoryInterface $timeInTransitRequestBuilderFactory
     */
    public function __construct(TimeInTransitRequestBuilderFactoryInterface $timeInTransitRequestBuilderFactory)
    {
        $this->timeInTransitRequestBuilderFactory = $timeInTransitRequestBuilderFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function createRequest(
        UPSTransport $transport,
        AddressInterface $shipFromAddress,
        AddressInterface $shipToAddress,
        \DateTime $pickupDate
    ) {
        $requestBuilder = $this->timeInTransitRequestBuilderFactory
            ->createTimeInTransitRequestBuilder($transport, $shipFromAddress, $shipToAddress, $pickupDate);

        return $requestBuilder->createRequest();
    }
}
