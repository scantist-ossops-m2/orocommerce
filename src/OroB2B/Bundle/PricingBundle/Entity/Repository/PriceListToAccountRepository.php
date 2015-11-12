<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class PriceListToAccountRepository extends EntityRepository
{
    /**
     * @param Account $account
     * @param Website $website
     * @return PriceList[]
     */
    public function getPriceListsByAccount(Account $account, Website $website)
    {
        return $this->createQueryBuilder('PriceListToAccount')
            ->innerJoin('PriceListToAccount.priceList', 'priceList')
            ->innerJoin('PriceListToAccount.account', 'account')
            ->where('account = :account')
            ->andWhere('PriceListToAccount.website = :website')
            ->setParameters(['account' => $account, 'website' => $website])
            ->getQuery()
            ->getResult();
    }
}
