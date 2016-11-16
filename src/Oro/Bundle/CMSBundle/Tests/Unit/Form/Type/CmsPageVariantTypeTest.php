<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Form\Type\CmsPageVariantType;
use Oro\Bundle\CMSBundle\Form\Type\PageSelectType;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentVariantType\Stub\ContentVariantStub;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type\Stub\ScopeCollectionTypeStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\Form\PreloadedExtension;

class CmsPageVariantTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var CmsPageVariantType
     */
    protected $type;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->type = new CmsPageVariantType($this->registry);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->type);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    ScopeCollectionType::NAME => new ScopeCollectionTypeStub(),
                    PageSelectType::NAME => new EntityType(
                        [
                            1 => $this->getEntity(Page::class, ['id' => 1]),
                            2 => $this->getEntity(Page::class, ['id' => 2]),
                        ],
                        PageSelectType::NAME
                    )
                ],
                []
            )
        ];
    }

    public function testBuildForm()
    {
        $this->assertMetadataCall();
        $form = $this->factory->create($this->type);

        $this->assertTrue($form->has('cmsPage'));
        $this->assertTrue($form->has('scopes'));
        $this->assertTrue($form->has('type'));
    }

    public function testGetName()
    {
        $this->assertEquals(CmsPageVariantType::NAME, $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(CmsPageVariantType::NAME, $this->type->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param ContentVariantInterface $existingData
     * @param array $submittedData
     * @param int $expectedPageId
     */
    public function testSubmit($existingData, $submittedData, $expectedPageId)
    {
        $this->assertMetadataCall();
        $form = $this->factory->create($this->type, $existingData);

        $this->assertEquals($existingData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        /** @var ContentVariantStub $actualData */
        $actualData = $form->getData();

        $this->assertEquals('cms_page', $actualData->getType());
        $this->assertEquals($expectedPageId, $actualData->getCmsPage()->getId());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        /** @var Page $page1 */
        $page1 = $this->getEntity(Page::class, ['id' => 1]);

        return [
            'new entity' => [
                'existingData' => new ContentVariantStub(),
                'submittedData' => [
                    'cmsPage' => 1
                ],
                'expectedPageId' => 1
            ],
            'existing entity' => [
                'existingData' => (new ContentVariantStub())
                    ->setCmsPage($page1)
                    ->setType(CmsPageContentVariantType::TYPE),
                'submittedData' => [
                    'cmsPage' => 2,
                    'type' => 'fakeType'
                ],
                'expectedPageId' => 2
            ],
        ];
    }

    protected function assertMetadataCall()
    {
        /** @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject $metadata */
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getName')
            ->willReturn(ContentVariantStub::class);
        
        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with(ContentVariantInterface::class)
            ->willReturn($metadata);
        $this->registry->expects($this->any())
            ->method('getManager')
            ->willReturn($em);
    }
}
