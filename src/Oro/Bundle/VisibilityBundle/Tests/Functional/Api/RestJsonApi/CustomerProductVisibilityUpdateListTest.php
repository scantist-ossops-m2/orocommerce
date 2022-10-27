<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;
use Oro\Bundle\VisibilityBundle\Async\Topic\ResolveProductVisibilityTopic;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;

/**
 * @group CommunityEdition
 *
 * @dbIsolationPerTest
 */
class CustomerProductVisibilityUpdateListTest extends RestJsonApiUpdateListTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->getOptionalListenerManager()->enableListener('oro_visibility.entity_listener.product_visibility_change');
        $this->loadFixtures([
            '@OroVisibilityBundle/Tests/Functional/Api/DataFixtures/customer_product_visibilities.yml',
        ]);
    }

    public function testCreateEntities(): void
    {
        $this->processUpdateList(
            CustomerProductVisibility::class,
            'update_list_create_customer_product_visibilities.yml'
        );

        $visibilityRepo = $this->getEntityManager()->getRepository(CustomerProductVisibility::class);
        $visibility1 = $visibilityRepo->findOneBy([
            'product' => $this->getReference('product-4')->getId(),
            'scope'   => $this->getReference('scope_3')->getId(),
        ]);
        $visibility2 = $visibilityRepo->findOneBy([
            'product' => $this->getReference('product-4')->getId(),
            'scope'   => $this->getReference('scope_2')->getId(),
        ]);
        self::assertMessagesSent(
            ResolveProductVisibilityTopic::getName(),
            [
                [
                    'entity_class_name' => CustomerProductVisibility::class,
                    'id'                => $visibility1->getId(),
                ],
                [
                    'entity_class_name' => CustomerProductVisibility::class,
                    'id'                => $visibility2->getId(),
                ],
            ]
        );

        $response = $this->cget(
            ['entity' => 'customerproductvisibilities'],
            ['filter[product][eq]' => '@product-4->id']
        );

        $this->assertResponseContains('update_list_create_customer_product_visibilities.yml', $response);
    }

    public function testUpdateEntities(): void
    {
        $visibility1Id = $this->getReference('visibility_1')->getId();
        $visibility2Id = $this->getReference('visibility_2')->getId();

        $data = [
            'data' => [
                [
                    'meta'       => ['update' => true],
                    'type'       => 'customerproductvisibilities',
                    'id'         => '<(implode("-", [@product-1->id, @customer.orphan->id]))>',
                    'attributes' => [
                        'visibility' => 'hidden',
                    ],
                ],
                [
                    'meta'       => ['update' => true],
                    'type'       => 'customerproductvisibilities',
                    'id'         => '<(implode("-", [@product-2->id, @customer.level_1_1->id]))>',
                    'attributes' => [
                        'visibility' => 'visible',
                    ],
                ],
            ],
        ];
        $this->processUpdateList(CustomerProductVisibility::class, $data);

        self::assertMessagesSent(
            ResolveProductVisibilityTopic::getName(),
            [
                [
                    'entity_class_name' => CustomerProductVisibility::class,
                    'id'                => $visibility1Id,
                ],
                [
                    'entity_class_name' => CustomerProductVisibility::class,
                    'id'                => $visibility2Id,
                ],
            ]
        );

        $response = $this->cget(
            ['entity' => 'customerproductvisibilities'],
            [
                'filter' => [
                    'id' => [
                        '<(implode("-", [@product-1->id, @customer.orphan->id]))>',
                        '<(implode("-", [@product-2->id, @customer.level_1_1->id]))>',
                    ],
                ],
            ]
        );
        $expectedData = $data;
        foreach ($expectedData['data'] as $key => $item) {
            unset($expectedData['data'][$key]['meta']);
        }
        $this->assertResponseContains($expectedData, $response);
    }

    public function testCreateAndUpdateEntities(): void
    {
        $data = [
            'data' => [
                [
                    'meta'       => ['update' => true],
                    'type'       => 'customerproductvisibilities',
                    'id'         => '<(implode("-", [@product-1->id, @customer.orphan->id]))>',
                    'attributes' => [
                        'visibility' => 'hidden',
                    ],
                ],
                [
                    'type'          => 'customerproductvisibilities',
                    'attributes'    => [
                        'visibility' => 'visible',
                    ],
                    'relationships' => [
                        'product'  => [
                            'data' => [
                                'type' => 'products',
                                'id'   => '<toString(@product-4->id)>',
                            ],
                        ],
                        'customer' => [
                            'data' => [
                                'type' => 'customers',
                                'id'   => '<toString(@customer.level_1.1->id)>',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->processUpdateList(CustomerProductVisibility::class, $data);

        $visibility1 = $this->getReference('visibility_1');
        $visibility2 = $this->getEntityManager()->getRepository(CustomerProductVisibility::class)->findOneBy(
            [
                'product' => $this->getReference('product-4')->getId(),
                'scope'   => $this->getReference('scope_3')->getId(),
            ]
        );
        self::assertMessagesSent(
            ResolveProductVisibilityTopic::getName(),
            [
                [
                    'entity_class_name' => CustomerProductVisibility::class,
                    'id'                => $visibility1->getId(),
                ],
                [
                    'entity_class_name' => CustomerProductVisibility::class,
                    'id'                => $visibility2->getId(),
                ],
            ]
        );

        $response = $this->cget(
            ['entity' => 'customerproductvisibilities'],
            [
                'filter' => [
                    'id' => [
                        '<(implode("-", [@product-1->id, @customer.orphan->id]))>',
                        '<(implode("-", [@product-4->id, @customer.level_1.1->id]))>',
                    ],
                ],
            ]
        );
        $expectedData['data'][1]['id'] = '<(implode("-", [@product-4->id, @customer.level_1.1->id]))>';
        unset($expectedData['data'][1]['meta']);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testTryToCreateEntitiesWithIncludes(): void
    {
        $data = [
            'data'     => [
                [
                    'type'          => 'customerproductvisibilities',
                    'attributes'    => [
                        'visibility' => 'visible',
                    ],
                    'relationships' => [
                        'product'  => [
                            'data' => [
                                'type' => 'products',
                                'id'   => '<toString(@product-4->id)>',
                            ],
                        ],
                        'customer' => [
                            'data' => [
                                'type' => 'customers',
                                'id'   => 'new_customer',
                            ],
                        ],
                    ],
                ],
            ],
            'included' => [
                [
                    'type'       => 'customers',
                    'id'         => 'new_customer',
                    'attributes' => [
                        'name' => 'New Customer',
                    ],
                ],
            ],
        ];
        $operationId = $this->processUpdateList(
            CustomerProductVisibility::class,
            $data,
            false
        );

        $this->assertAsyncOperationErrors(
            [
                [
                    'id'     => $operationId . '-1-1',
                    'status' => 400,
                    'title'  => 'request data constraint',
                    'detail' => 'The included data are not supported for this resource type.',
                    'source' => ['pointer' => '/included'],
                ],
            ],
            $operationId
        );
    }
}
