<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SplitCheckoutProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SplitEntitiesProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SplitEntitiesProviderTest extends TestCase
{
    private SplitCheckoutProvider|MockObject $splitCheckoutProvider;
    private SplitEntitiesProvider $provider;

    protected function setUp(): void
    {
        $this->splitCheckoutProvider = $this->createMock(SplitCheckoutProvider::class);
        $this->provider = new SplitEntitiesProvider($this->splitCheckoutProvider);
    }

    /**
     * @param array $splitCheckouts
     * @param int $expectedCount
     * @dataProvider getTestGetSplitEntitiesWithCheckoutEntityData
     */
    public function testGetSplitEntitiesWithCheckoutEntity(array $splitCheckouts, int $expectedCount)
    {
        $this->splitCheckoutProvider->expects($this->once())
            ->method('getSubCheckouts')
            ->willReturn($splitCheckouts);

        $subCheckouts = $this->provider->getSplitEntities(new Checkout());
        $this->assertCount($expectedCount, $subCheckouts);
    }

    private function getTestGetSplitEntitiesWithCheckoutEntityData()
    {
        return [
            [
                'splitCheckouts' => [],
                'expectedCount' => 0
            ],
            [
                'splitCheckouts' => [new Checkout(), new Checkout()],
                'expectedCount' => 2
            ]
        ];
    }

    /**
     * @param Order $order
     * @param int $expectedCount
     * @dataProvider getTestGetSplitEntitiesWithOrderEntityData
     */
    public function testGetSplitEntitiesWithOrderEntity(Order $order, int $expectedCount)
    {
        $this->splitCheckoutProvider->expects($this->never())
            ->method('getSubCheckouts');

        $subEntities = $this->provider->getSplitEntities($order);
        $this->assertCount($expectedCount, $subEntities);
    }

    private function getTestGetSplitEntitiesWithOrderEntityData()
    {
        $orderWithSuborders = new Order();
        $orderWithSuborders->addSubOrder(new Order());
        $orderWithSuborders->addSubOrder(new Order());

        return [
            [
                'order' => new Order(),
                'expectedCount' => 0
            ],
            [
                'order' => $orderWithSuborders,
                'expectedCount' => 2
            ]
        ];
    }

    public function testGetSplitEntitiesWithNotSupportedEntity()
    {
        $this->splitCheckoutProvider->expects($this->never())
            ->method('getSubCheckouts');

        $subEntities = $this->provider->getSplitEntities(new ShoppingList());
        $this->assertEmpty($subEntities);
    }
}
