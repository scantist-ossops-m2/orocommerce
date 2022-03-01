<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Resolves price lists rules and updates actuality of price lists.
 */
class PriceRuleProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private ManagerRegistry $doctrine;
    private LoggerInterface $logger;
    private ProductPriceBuilder $priceBuilder;
    private NotificationAlertManager $notificationAlertManager;
    private PriceListTriggerHandler $triggerHandler;

    public function __construct(
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        ProductPriceBuilder $priceBuilder,
        NotificationAlertManager $notificationAlertManager,
        PriceListTriggerHandler $triggerHandler
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->priceBuilder = $priceBuilder;
        $this->notificationAlertManager = $notificationAlertManager;
        $this->triggerHandler = $triggerHandler;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [ResolvePriceRulesTopic::getName()];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = $message->getBody();
        $priceListsCount = count($body['product']);

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(PriceList::class);
        foreach ($body['product'] as $priceListId => $productIds) {
            /** @var PriceList|null $priceList */
            $priceList = $em->find(PriceList::class, $priceListId);
            if (null === $priceList) {
                $this->logger->warning(sprintf(
                    'PriceList entity with identifier %s not found.',
                    $priceListId
                ));
                continue;
            }

            $em->beginTransaction();
            try {
                $this->notificationAlertManager->resolveNotificationAlertByOperationAndItemIdForCurrentUser(
                    PriceListCalculationNotificationAlert::OPERATION_PRICE_RULES_BUILD,
                    $priceList->getId()
                );

                $this->processPriceList($em, $priceList, $productIds);

                $em->commit();
            } catch (\Exception $e) {
                $em->rollback();
                $this->logger->error(
                    'Unexpected exception occurred during Price Rule build.',
                    ['exception' => $e]
                );

                if ($e instanceof RetryableException) {
                    // On RetryableException send back to queue the message related to a single price list
                    // that triggered an exception.
                    // If this was the only one PL in the message REQUEUE it to persist retries counter
                    if ($priceListsCount === 1) {
                        return self::REQUEUE;
                    }

                    $this->triggerHandler->handlePriceListTopic(
                        ResolvePriceRulesTopic::getName(),
                        $priceList,
                        $productIds
                    );
                } else {
                    $this->notificationAlertManager->addNotificationAlert(
                        PriceListCalculationNotificationAlert::createForPriceRulesBuildError(
                            $priceListId,
                            $e->getMessage()
                        )
                    );
                    if ($priceListsCount === 1) {
                        return self::REJECT;
                    }
                }
            }
        }

        return self::ACK;
    }

    /**
     * @param EntityManagerInterface $em
     * @param PriceList $priceList
     * @param int[] $productIds
     */
    private function processPriceList(EntityManagerInterface $em, PriceList $priceList, array $productIds): void
    {
        $startTime = $priceList->getUpdatedAt();
        $this->priceBuilder->buildByPriceList($priceList, $productIds);
        $this->updatePriceListActuality($em, $priceList, $startTime);
    }

    private function updatePriceListActuality(
        EntityManagerInterface $em,
        PriceList $priceList,
        \DateTime $startTime
    ): void {
        $em->refresh($priceList);
        if ($startTime == $priceList->getUpdatedAt()) {
            /** @var PriceListRepository $repo */
            $repo = $em->getRepository(PriceList::class);
            $repo->updatePriceListsActuality([$priceList], true);
        }
    }
}
