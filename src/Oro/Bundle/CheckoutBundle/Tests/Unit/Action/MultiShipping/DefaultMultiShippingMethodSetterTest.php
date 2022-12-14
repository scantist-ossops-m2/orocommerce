<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action\MultiShipping;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Action\MultiShipping\DefaultMultiShippingMethodSetter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemsShippingManager;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\DefaultMultipleShippingMethodProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethod;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethodType;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class DefaultMultiShippingMethodSetterTest extends TestCase
{
    use EntityTrait;

    private DefaultMultipleShippingMethodProvider|MockObject $shippingProvider;
    private CheckoutShippingMethodsProviderInterface|MockObject $shippingPriceProvider;
    private CheckoutLineItemsShippingManager|MockObject $lineItemsShippingManager;
    private ManagerRegistry|MockObject $doctrine;
    private ObjectManager|MockObject $manager;

    private DefaultMultiShippingMethodSetter $defaultMultiShippingMethodSetter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shippingProvider = $this->createMock(DefaultMultipleShippingMethodProvider::class);
        $this->shippingPriceProvider = $this->createMock(CheckoutShippingMethodsProviderInterface::class);
        $this->lineItemsShippingManager = $this->createMock(CheckoutLineItemsShippingManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->manager = $this->createMock(ObjectManager::class);

        $this->defaultMultiShippingMethodSetter = new DefaultMultiShippingMethodSetter(
            $this->shippingProvider,
            $this->shippingPriceProvider,
            $this->doctrine,
            $this->lineItemsShippingManager
        );
    }

    public function testSetDefaultShippingMethods()
    {
        $lineItem1 = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem1, 1);

        $checkout = new Checkout();
        $checkout->setLineItems(new ArrayCollection([$lineItem1]));

        $lineItemsShippingMethods = [
            'sku-1:item' => [
                'method' => 'flat_rate_1',
                'type' => 'primary',
            ],
            'sku-2:item' => [
                'identifier' => 'flat_rate_2',
                'type' => 'primary'
            ],
        ];

        $multiShippingMethod = $this->createMock(MultiShippingMethod::class);
        $multiShippingMethodType = $this->createMock(MultiShippingMethodType::class);
        $multiShippingMethodType->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('multi_shipping_type');

        $multiShippingMethod->expects($this->once())
            ->method('getTypes')
            ->willReturn([$multiShippingMethodType]);

        $multiShippingMethod->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('multi_shipping');

        $this->shippingProvider->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn($multiShippingMethod);

        $this->lineItemsShippingManager->expects($this->once())
            ->method('updateLineItemsShippingMethods')
            ->with($lineItemsShippingMethods, $checkout, true);

        $this->lineItemsShippingManager->expects($this->once())
            ->method('updateLineItemsShippingPrices')
            ->with($checkout);

        $this->shippingPriceProvider->expects($this->once())
            ->method('getPrice')
            ->with($checkout)
            ->willReturn(Price::create(15.00, 'USD'));

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(CheckoutLineItem::class)
            ->willReturn($this->manager);

        $this->manager->expects($this->once())
            ->method('flush');

        $this->defaultMultiShippingMethodSetter->setDefaultShippingMethods($checkout, $lineItemsShippingMethods, true);

        $this->assertEquals('multi_shipping', $checkout->getShippingMethod());
        $this->assertEquals('multi_shipping_type', $checkout->getShippingMethodType());
        $this->assertEquals(15.00, $checkout->getShippingCost()->getValue());
        $this->assertEquals('USD', $checkout->getShippingCost()->getCurrency());
    }

    public function testSetDefaultShippingMethodWithoutShippingCost()
    {
        $lineItem1 = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem1, 1);

        $checkout = new Checkout();
        $checkout->setLineItems(new ArrayCollection([$lineItem1]));

        $lineItemsShippingMethods = [
            'sku-1:item' => [
                'method' => 'flat_rate_1',
                'type' => 'primary',
            ],
            'sku-2:item' => [
                'identifier' => 'flat_rate_2',
                'type' => 'primary'
            ],
        ];

        $multiShippingMethod = $this->createMock(MultiShippingMethod::class);
        $multiShippingMethodType = $this->createMock(MultiShippingMethodType::class);
        $multiShippingMethodType->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('multi_shipping_type');

        $multiShippingMethod->expects($this->once())
            ->method('getTypes')
            ->willReturn([$multiShippingMethodType]);

        $multiShippingMethod->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('multi_shipping');

        $this->shippingProvider->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn($multiShippingMethod);

        $this->lineItemsShippingManager->expects($this->once())
            ->method('updateLineItemsShippingMethods')
            ->with($lineItemsShippingMethods, $checkout, false);

        $this->lineItemsShippingManager->expects($this->once())
            ->method('updateLineItemsShippingPrices')
            ->with($checkout);

        $this->shippingPriceProvider->expects($this->once())
            ->method('getPrice')
            ->with($checkout)
            ->willReturn(null);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(CheckoutLineItem::class)
            ->willReturn($this->manager);

        $this->manager->expects($this->once())
            ->method('flush');

        $this->defaultMultiShippingMethodSetter->setDefaultShippingMethods($checkout, $lineItemsShippingMethods, false);

        $this->assertEquals('multi_shipping', $checkout->getShippingMethod());
        $this->assertEquals('multi_shipping_type', $checkout->getShippingMethodType());
        $this->assertNull($checkout->getShippingCost());
    }
}
