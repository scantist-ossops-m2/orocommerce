<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\InventoryBundle\EventListener\ProductManageInventoryFormViewListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Component\Testing\Unit\FormViewListenerTestCase;

class ProductManageInventoryFormViewListenerTest extends FormViewListenerTestCase
{
    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var ProductManageInventoryFormViewListener
     */
    protected $productWarehouseFormViewListener;

    /** @var BeforeListRenderEvent|\PHPUnit_Framework_MockObject_MockObject * */
    protected $event;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrine;

    protected function setUp()
    {
        parent::setUp();
        $this->requestStack = $this->getMock(RequestStack::class);

        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->event = $this->getBeforeListRenderEventMock();
        $this->productWarehouseFormViewListener = new ProductManageInventoryFormViewListener(
            $this->requestStack,
            $this->doctrine,
            $this->translator
        );
    }

    public function testOnProductViewIgnoredIfNoProductId()
    {
        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');

        $this->productWarehouseFormViewListener->onProductView($this->event);
    }

    public function testOnProductViewIgnoredIfNoProductFound()
    {
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($this->em);

        $this->request->expects($this->once())
            ->method('get')
            ->willReturn('1');

        $this->event->expects($this->never())
            ->method('getEnvironment');

        $this->productWarehouseFormViewListener->onProductView($this->event);
    }

    public function testOnProductViewRendersAndAddsSubBlock()
    {
        $this->request->expects($this->once())
            ->method('get')
            ->willReturn('1');

        $product = new Product();

        $this->em->expects($this->once())
            ->method('getReference')
            ->willReturn($product);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($this->em);
        $env = $this->getMockBuilder(\Twig_Environment::class)->disableOriginalConstructor()->getMock();
        $this->event->expects($this->once())->method('getEnvironment')->willReturn($env);
        $scrollData = $this->getMock(ScrollData::class);
        $this->event->expects($this->once())->method('getScrollData')->willReturn($scrollData);
        $scrollData->expects($this->once())->method('getData')->willReturn(
            ['dataBlocks' => [1 => ['title' => 'oro.product.sections.inventory.trans']]]
        );
        $this->productWarehouseFormViewListener->onProductView($this->event);
    }
}
