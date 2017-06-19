<?php

namespace Oro\Bundle\CouponBundle\Tests\Functional\Controller;

use Oro\Bundle\CouponBundle\Entity\Coupon;
use Oro\Bundle\CouponBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CouponControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                LoadCouponData::class,
            ]
        );
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_coupon_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('coupons-grid', $crawler->html());
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_coupon_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Create Coupon', $crawler->html());
    }

    public function testUpdate()
    {
        /** @var Coupon $coupon*/
        $coupon = $this->getContainer()->get('doctrine')
            ->getManagerForClass(Coupon::class)
            ->getRepository(Coupon::class)
            ->findOneBy([]);
        $this->client->request('GET', $this->getUrl('oro_coupon_update', ['id' => $coupon->getId()]));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testView()
    {
        /** @var Coupon $coupon*/
        $coupon = $this->getContainer()->get('doctrine')
            ->getManagerForClass(Coupon::class)
            ->getRepository(Coupon::class)
            ->findOneBy([]);
        $this->client->request('GET', $this->getUrl('oro_coupon_view', ['id' => $coupon->getId()]));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }
}
