<?php

namespace Oro\Bundle\PricingBundle\Debug\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Get prices by price list with info about prices selected by merge strategy.
 *
 * @internal This service is applicable for pricing debug purpose only.
 */
class PriceMergeInfoProvider
{
    /** @var array|SelectedPriceProviderInterface[] */
    private array $providers = [];

    public function __construct(
        private ConfigManager $configManager,
        private ManagerRegistry $registry,
        private ShardManager $shardManager
    ) {
    }

    public function addSelectedPriceProvider(string $strategy, SelectedPriceProviderInterface $provider): void
    {
        $this->providers[$strategy] = $provider;
    }

    public function getPriceMergingDetails(array $priceListRelations, Product $product): array
    {
        $selectedPriceIds = $this->getSelectedPriceIds($priceListRelations, $product);

        $priceRepo = $this->registry->getRepository(ProductPrice::class);
        $result = [];
        foreach ($priceListRelations as $priceListRelation) {
            $priceList = $priceListRelation->getPriceList();
            $prices = $priceRepo->findByPriceList(
                $this->shardManager,
                $priceList,
                ['product' => $product],
                ['unit' => 'ASC', 'currency' => 'ASC', 'quantity' => 'ASC']
            );
            foreach ($prices as $price) {
                $result[$priceList->getId()][] = [
                    'price' => $price,
                    'is_selected' => \in_array($price->getId(), $selectedPriceIds)
                ];
            }
        }

        return $result;
    }

    public function isMergedPricesSameToCurrentPrices(array $mergedPrices, array $currentPrices): bool
    {
        $calculatedPrices = [];
        foreach ($mergedPrices as $priceRows) {
            foreach ($priceRows as $priceRow) {
                if (empty($priceRow['is_selected'])) {
                    continue;
                }
                /** @var ProductPrice $priceData */
                $priceData = $priceRow['price'];
                $calculatedPrices[$priceData->getPrice()->getCurrency()][] = sprintf(
                    '%s_%s_%s',
                    $priceData->getProductUnitCode(),
                    (float)$priceData->getPrice()->getValue(),
                    $priceData->getQuantity()
                );
            }
        }

        if (count($calculatedPrices) !== count($currentPrices)) {
            return false;
        }

        foreach ($currentPrices as $currency => $prices) {
            if (!array_key_exists($currency, $calculatedPrices)) {
                return false;
            }

            if (count($prices) !== count($calculatedPrices[$currency])) {
                return false;
            }

            $prices = array_map(
                fn (array $row) => sprintf('%s_%s_%s', $row['unitCode'], $row['price']->getValue(), $row['quantity']),
                $prices
            );

            if (array_diff($prices, $calculatedPrices[$currency])) {
                return false;
            }
        }

        return true;
    }

    private function getSelectedPriceIds(array $priceListRelations, Product $product): array
    {
        $strategy = $this->configManager->get('oro_pricing.price_strategy');

        if (array_key_exists($strategy, $this->providers)) {
            return $this->providers[$strategy]->getSelectedPricesIds($priceListRelations, $product);
        }

        return [];
    }
}
