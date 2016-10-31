<?php

namespace Oro\Bundle\OrderBundle\Twig;

use Oro\Bundle\OrderBundle\Formatter\ShippingMethodFormatter;
use Oro\Bundle\OrderBundle\Formatter\ShippingTrackingFormatter;
use Oro\Bundle\OrderBundle\Formatter\SourceDocumentFormatter;

class OrderExtension extends \Twig_Extension
{
    const NAME = 'oro_order_order';

    /**
     * @var SourceDocumentFormatter
     */
    protected $sourceDocumentFormatter;

    /**
     * @var ShippingTrackingFormatter
     */
    protected $shippingTrackingFormatter;

    /**
     * @var ShippingMethodFormatter
     */
    protected $shippingMethodFormatter;

    /**
     * @param SourceDocumentFormatter $sourceDocumentFormatter
     * @param ShippingTrackingFormatter $shippingTrackingFormatter
     * @param ShippingMethodFormatter $shippingMethodFormatter
     */
    public function __construct(
        SourceDocumentFormatter $sourceDocumentFormatter,
        ShippingTrackingFormatter $shippingTrackingFormatter,
        ShippingMethodFormatter $shippingMethodFormatter
    ) {
        $this->sourceDocumentFormatter = $sourceDocumentFormatter;
        $this->shippingTrackingFormatter = $shippingTrackingFormatter;
        $this->shippingMethodFormatter = $shippingMethodFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'oro_order_format_source_document',
                [$this->sourceDocumentFormatter, 'format'],
                ['is_safe' => ['html']]
            )
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'oro_order_format_shipping_tracking_method',
                [$this->shippingTrackingFormatter, 'formatShippingTrackingMethod'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'oro_order_format_shipping_tracking_link',
                [$this->shippingTrackingFormatter, 'formatShippingTrackingLink'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'oro_order_shipping_method_label',
                [$this->shippingMethodFormatter, 'formatShippingMethodLabel'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'oro_order_shipping_type_label',
                [$this->shippingMethodFormatter, 'formatShippingTypeLabel'],
                ['is_safe' => ['html']]
            )
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
