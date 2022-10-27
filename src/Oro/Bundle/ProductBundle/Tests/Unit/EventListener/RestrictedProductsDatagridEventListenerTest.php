<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\EventListener\RestrictedProductsDatagridEventListener;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RestrictedProductsDatagridEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $productManager;

    /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $qb;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    protected $requestStack;

    /** @var RestrictedProductsDatagridEventListener */
    protected $listener;

    protected function setUp(): void
    {
        $this->qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productManager = $this->getMockBuilder(ProductManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new RestrictedProductsDatagridEventListener($this->requestStack, $this->productManager);
    }

    /**
     * @dataProvider testOnBuildAfterDataProvider
     * @param Request|null $request
     * @param array $expectedParamsResult
     */
    public function testOnBuildAfter($request, array $expectedParamsResult)
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $event = $this->createEvent();
        $this->productManager->expects($this->once())
            ->method('restrictQueryBuilder')
            ->with(
                $this->qb,
                $expectedParamsResult
            );
        $this->listener->onBuildAfter($event);
    }

    /**
     * @return array
     */
    public function testOnBuildAfterDataProvider()
    {
        $emptyParamsRequest = new Request();
        $emptyParamsRequest->request->set(ProductSelectType::DATA_PARAMETERS, []);
        $params = ['some' => 'param'];
        $notEmptyParamsRequest = new Request();
        $notEmptyParamsRequest->request->set(ProductSelectType::DATA_PARAMETERS, $params);

        return
            [
                'withoutRequest' => ['request' => null, 'expectedParamsResult' => []],
                'withoutParams' => ['request' => new Request(), 'expectedParamsResult' => []],
                'withEmptyParams' => ['request' => $emptyParamsRequest, 'expectedParamsResult' => []],
                'withNotEmptyParams' => ['request' => $notEmptyParamsRequest, 'expectedParamsResult' => $params],
            ];
    }

    /**
     * @return BuildAfter
     */
    protected function createEvent()
    {
        /** @var OrmDatasource|\PHPUnit\Framework\MockObject\MockObject $dataSource */
        $dataSource = $this->getMockBuilder(OrmDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dataSource->expects($this->once())->method('getQueryBuilder')->willReturn($this->qb);

        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $dataGrid */
        $dataGrid = $this->createMock(DatagridInterface::class);
        $dataGrid->expects($this->once())->method('getDatasource')->willReturn($dataSource);

        return new BuildAfter($dataGrid);
    }
}
