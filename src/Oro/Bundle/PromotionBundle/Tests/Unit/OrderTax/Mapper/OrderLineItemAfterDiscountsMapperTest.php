<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\OrderTax\Mapper;

use Brick\Math\BigDecimal;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\PromotionBundle\OrderTax\Mapper\OrderLineItemAfterDiscountsMapper;
use Oro\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class OrderLineItemAfterDiscountsMapperTest extends \PHPUnit\Framework\TestCase
{
    /** @var TaxMapperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerMapper;

    /** @var TaxationSettingsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $taxationSettingsProvider;

    /** @var PromotionExecutor|\PHPUnit\Framework\MockObject\MockObject */
    private $promotionExecutor;

    private OrderLineItemAfterDiscountsMapper $orderLineItemAfterDiscountsMapper;

    protected function setUp(): void
    {
        $this->innerMapper = $this->createMock(TaxMapperInterface::class);
        $this->taxationSettingsProvider = $this->createMock(TaxationSettingsProvider::class);
        $this->promotionExecutor = $this->createMock(PromotionExecutor::class);

        $this->orderLineItemAfterDiscountsMapper = new OrderLineItemAfterDiscountsMapper(
            $this->innerMapper,
            $this->taxationSettingsProvider,
            $this->promotionExecutor
        );
    }

    public function testMapLineItemNoPrice(): void
    {
        $taxable = new Taxable();
        $taxable->setAmount(4);
        $taxable->setShippingCost(2);

        $lineItem = new OrderLineItem();
        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($lineItem)
            ->willReturn($taxable);

        $this->taxationSettingsProvider->expects(self::never())
            ->method('isCalculateAfterPromotionsEnabled');

        $this->promotionExecutor->expects(self::never())
            ->method('supports')
            ->withAnyParameters();
        $this->promotionExecutor->expects(self::never())
            ->method('execute')
            ->withAnyParameters();

        self::assertEquals(clone $taxable, $this->orderLineItemAfterDiscountsMapper->map($lineItem));
    }

    public function testMapCalculateAfterPromotionsDisabled(): void
    {
        $taxable = new Taxable();
        $taxable->setPrice(4);

        $price = new Price();
        $lineItem = new OrderLineItem();
        $lineItem->setPrice($price);
        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($lineItem)
            ->willReturn($taxable);

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(false);

        $this->promotionExecutor->expects(self::never())
            ->method('supports')
            ->withAnyParameters();
        $this->promotionExecutor->expects(self::never())
            ->method('execute')
            ->withAnyParameters();

        self::assertEquals(clone $taxable, $this->orderLineItemAfterDiscountsMapper->map($lineItem));
    }

    public function testMapExecutorNotSupportedEntity(): void
    {
        $taxable = new Taxable();
        $taxable->setPrice(4);

        $order = new Order();
        $price = new Price();
        $lineItem = new OrderLineItem();
        $lineItem
            ->setPrice($price)
            ->setOrder($order);
        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($lineItem)
            ->willReturn($taxable);

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(true);

        $this->promotionExecutor->expects(self::once())
            ->method('supports')
            ->with($order)
            ->willReturn(false);
        $this->promotionExecutor->expects(self::never())
            ->method('execute')
            ->withAnyParameters();

        self::assertEquals(clone $taxable, $this->orderLineItemAfterDiscountsMapper->map($lineItem));
    }

    public function testMapNoLineItems(): void
    {
        $taxable = new Taxable();
        $taxable->setPrice(4);

        $order = new Order();
        $price = new Price();
        $lineItem = new OrderLineItem();
        $lineItem
            ->setPrice($price)
            ->setOrder($order);
        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($lineItem)
            ->willReturn($taxable);

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(true);

        $discountContext = new DiscountContext();
        $this->promotionExecutor->expects(self::once())
            ->method('supports')
            ->with($order)
            ->willReturn(true);
        $this->promotionExecutor->expects(self::once())
            ->method('execute')
            ->with($order)
            ->willReturn($discountContext);

        self::assertEquals(clone $taxable, $this->orderLineItemAfterDiscountsMapper->map($lineItem));
    }

    public function testMapWrongSourceLineItem(): void
    {
        $taxable = new Taxable();
        $taxable->setPrice(4);

        $order = new Order();
        $price = new Price();
        $lineItem = new OrderLineItem();
        $lineItem
            ->setPrice($price)
            ->setOrder($order);
        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($lineItem)
            ->willReturn($taxable);

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(true);

        $discountLineItem = new DiscountLineItem();
        $discountContext = new DiscountContext();
        $discountContext->setLineItems([$discountLineItem]);

        $this->promotionExecutor->expects(self::once())
            ->method('supports')
            ->with($order)
            ->willReturn(true);
        $this->promotionExecutor->expects(self::once())
            ->method('execute')
            ->with($order)
            ->willReturn($discountContext);

        self::assertEquals(clone $taxable, $this->orderLineItemAfterDiscountsMapper->map($lineItem));
    }

    public function testMap(): void
    {
        $taxable = new Taxable();
        $taxable->setPrice(4);

        $order = new Order();
        $price = new Price();
        $lineItem = new OrderLineItem();
        $lineItem
            ->setPrice($price)
            ->setOrder($order);
        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($lineItem)
            ->willReturn($taxable);

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(true);

        $discountLineItem = new DiscountLineItem();
        $discountLineItem
            ->setSourceLineItem($lineItem)
            ->setSubtotal(2);
        $discountContext = new DiscountContext();
        $discountContext->setLineItems([$discountLineItem]);

        $this->promotionExecutor->expects(self::once())
            ->method('supports')
            ->with($order)
            ->willReturn(true);
        $this->promotionExecutor->expects(self::once())
            ->method('execute')
            ->with($order)
            ->willReturn($discountContext);

        $expectedTaxable = clone $taxable;
        $expectedTaxable->setPrice(BigDecimal::of(2)->toScale(TaxationSettingsProvider::CALCULATION_SCALE));

        self::assertEquals($expectedTaxable, $this->orderLineItemAfterDiscountsMapper->map($lineItem));
    }
}
