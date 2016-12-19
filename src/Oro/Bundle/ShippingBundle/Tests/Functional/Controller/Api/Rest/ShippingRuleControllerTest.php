<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ShippingMethodsConfigsRuleControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([]);
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                'Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingMethodsConfigsRules',
                'Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadUserData'
            ]
        );
    }

    public function testDisableAction()
    {
        /** @var ShippingMethodsConfigsRule $shippingRule */
        $shippingRule = $this->getReference('shipping_rule.1');
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_disable_shippingrules', ['id' => $shippingRule->getId()]),
            [],
            [],
            static::generateWsseAuthHeader(LoadUserData::USER_EDITOR, LoadUserData::USER_EDITOR)
        );
        $result = $this->client->getResponse();
        static::assertJsonResponseStatusCodeEquals($result, 200);
        static::assertEquals(false, $this->getReference('shipping_rule.1')->isEnabled());
    }

    /**
     * @depends testDisableAction
     */
    public function testEnableAction()
    {
        /** @var ShippingMethodsConfigsRule $shippingRule */
        $shippingRule = $this->getReference('shipping_rule.1');
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_enable_shippingrules', ['id' => $shippingRule->getId()]),
            [],
            [],
            static::generateWsseAuthHeader(LoadUserData::USER_EDITOR, LoadUserData::USER_EDITOR)
        );
        $result = $this->client->getResponse();
        static::assertJsonResponseStatusCodeEquals($result, 200);
        static::assertEquals(true, $this->getReference('shipping_rule.1')->isEnabled());
    }

    /*
    public function testDelete()
    {
        /** @var ShippingMethodsConfigsRule $shippingRule */
        /*$shippingRule = $this->getReference('shipping_rule.1');
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_shippingrules', ['id' => $shippingRule->getId()]),
            [],
            [],
            static::generateWsseAuthHeader()
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }*/

    public function testDeleteWOPermission()
    {
        /** @var ShippingMethodsConfigsRule $shippingRule */
        $shippingRule = $this->getReference('shipping_rule.1');
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_shippingrules', ['id' => $shippingRule->getId()]),
            [],
            [],
            static::generateWsseAuthHeader(LoadUserData::USER_VIEWER, LoadUserData::USER_VIEWER)
        );
        $result = $this->client->getResponse();
        static::assertJsonResponseStatusCodeEquals($result, 403);
    }
}
