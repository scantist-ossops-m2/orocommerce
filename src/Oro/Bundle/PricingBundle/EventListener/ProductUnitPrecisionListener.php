<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Event\ProductPricesRemoveAfter;
use Oro\Bundle\PricingBundle\Event\ProductPricesRemoveBefore;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Remove product prices by unit on ProductUnitPrecision delete.
 * Schedule price recalculation on ProductUnitPrecision persist or update.
 */
class ProductUnitPrecisionListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    protected string $productPriceClass;
    protected EventDispatcherInterface $eventDispatcher;
    protected ShardManager $shardManager;
    private DoctrineHelper $doctrineHelper;
    private PriceListTriggerHandler $priceListTriggerHandler;
    private array $scheduledProductIds = [];

    public function __construct(
        $productPriceClass,
        EventDispatcherInterface $dispatcher,
        ShardManager $shardManager,
        DoctrineHelper $helper,
        PriceListTriggerHandler $priceListTriggerHandler
    ) {
        $this->productPriceClass = $productPriceClass;
        $this->eventDispatcher = $dispatcher;
        $this->shardManager = $shardManager;
        $this->doctrineHelper = $helper;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
    }

    public function postRemove(ProductUnitPrecision $precision, LifecycleEventArgs $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $product = $precision->getProduct();
        $unit = $precision->getUnit();
        // prices are already removed using cascade delete operation
        if (!$product->getId()) {
            return;
        }
        $args = ['unit' => $product, 'product' => $unit];
        $this->eventDispatcher
            ->dispatch(new ProductPricesRemoveBefore($args), ProductPricesRemoveBefore::NAME);

        /** @var ProductPriceRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository($this->productPriceClass);
        $repository->deleteByProductUnit($this->shardManager, $product, $unit);
        $this->eventDispatcher
            ->dispatch(new ProductPricesRemoveAfter($args), ProductPricesRemoveAfter::NAME);
    }

    public function onFlush(OnFlushEventArgs $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $uow = $event->getObjectManager()->getUnitOfWork();
        foreach ($uow->getScheduledEntityInsertions() as $insertion) {
            if ($insertion instanceof ProductUnitPrecision) {
                $scheduledProductIds[$insertion->getProduct()->getId()] = true;
            }
        }

        $expectedChanges = ['product', 'unit', 'precision'];
        foreach ($uow->getScheduledEntityUpdates() as $update) {
            if (!$update instanceof ProductUnitPrecision) {
                continue;
            }

            $changeSet = $uow->getEntityChangeSet($update);
            if (!array_intersect(array_keys($changeSet), $expectedChanges)) {
                continue;
            }

            $this->scheduledProductIds[$update->getProduct()->getId()] = true;
        }


    }

    public function postFlush()
    {
        if (!$this->scheduledProductIds) {
            return;
        }

        /** @var PriceListRepository $plRepo */
        $plRepo = $this->doctrineHelper->getEntityRepository(PriceList::class);
        $productIds = array_keys($this->scheduledProductIds);
        foreach ($plRepo->getPriceListsWithRulesByAssignedProducts($productIds) as $priceList) {
            $this->priceListTriggerHandler->handlePriceListTopic(
                ResolvePriceRulesTopic::getName(),
                $priceList,
                $productIds
            );
        }
    }
}
