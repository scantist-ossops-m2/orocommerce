<?php

namespace Oro\Bundle\CouponBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CouponBundle\Entity\Coupon;

class LoadCouponData extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
        $coupon = new Coupon();
        $coupon
            ->setCode('test123')
            ->setUsesPerCoupon(1)
            ->setUsesPerUser(1);

        $manager->persist($coupon);
        $manager->flush();
    }
}
