<?php

namespace Oro\Bundle\VisibilityBundle\Async\Visibility;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\VisibilityBundle\Async\Topics;
use Oro\Bundle\VisibilityBundle\Driver\CustomerPartialUpdateDriverInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Updated visibility for a customer.
 */
class CustomerProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var LoggerInterface */
    private $logger;

    /** @var CustomerPartialUpdateDriverInterface */
    private $partialUpdateDriver;

    public function __construct(
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        CustomerPartialUpdateDriverInterface $partialUpdateDriver
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->partialUpdateDriver = $partialUpdateDriver;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::CHANGE_CUSTOMER];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());
        if (!isset($body['id'])) {
            $this->logger->critical('Got invalid message.');

            return self::REJECT;
        }

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(BaseVisibilityResolved::class);
        $em->beginTransaction();
        try {
            $customer = $this->getCustomer($body['id']);
            $this->partialUpdateDriver->updateCustomerVisibility($customer);
            $em->commit();
        } catch (UniqueConstraintViolationException $e) {
            $em->rollback();
            $this->logger->warning(
                'Couldn`t create scope because the scope already created with the same data.',
                ['exception' => $e]
            );

            return self::REJECT;
        } catch (\Exception $e) {
            $em->rollback();
            $this->logger->error(
                'Unexpected exception occurred during update Customer Visibility.',
                ['exception' => $e]
            );

            if ($e instanceof EntityNotFoundException) {
                return self::REJECT;
            }

            return self::REQUEUE;
        }

        return self::ACK;
    }

    /**
     * @throws EntityNotFoundException if a customer does not exist
     */
    public function getCustomer(int $customerId): Customer
    {
        /** @var Customer|null $customer */
        $customer = $this->doctrine->getManagerForClass(Customer::class)
            ->find(Customer::class, $customerId);
        if (null === $customer) {
            throw new EntityNotFoundException('Customer was not found.');
        }

        return $customer;
    }
}
