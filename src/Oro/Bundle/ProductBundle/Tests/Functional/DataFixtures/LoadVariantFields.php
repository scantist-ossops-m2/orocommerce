<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestContentVariant;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Schema\OroFrontendTestFrameworkBundleInstaller;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\MakeProductAttributesTrait;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;

class LoadVariantFields extends AbstractFixture
{
    use MakeProductAttributesTrait;

    /** @var array */
    private $selectOptions = [
        'Good'     => true,
        'Better' => false,
        'The best' => false,
    ];

    const VARIANT_FIELD_COLOR = 'color';
    const VARIANT_FIELD_SIZE = 'size';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $className = ExtendHelper::buildEnumValueClassName(OroFrontendTestFrameworkBundleInstaller::VARIANT_FIELD_CODE);

        /** @var EnumValueRepository $enumRepo */
        $enumRepo = $manager->getRepository($className);

        $priority = 1;
        foreach ($this->selectOptions as $name => $isDefault) {
            $enumOption = $enumRepo->createEnumValue($name, $priority++, $isDefault);
            $manager->persist($enumOption);
        }

        $this->makeProductAttributes(
            [
                OroFrontendTestFrameworkBundleInstaller::VARIANT_FIELD_NAME => []
            ],
            ExtendScope::OWNER_CUSTOM
        );

        $defaultFamily = $manager->getRepository(AttributeFamily::class)
            ->findOneBy(['code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE]);

        $attributeGroup = $defaultFamily->getAttributeGroup(LoadProductDefaultAttributeFamilyData::GENERAL_GROUP_CODE);

        $configManager = $this->container->get('oro_entity_config.config_manager');
        $variantField = $configManager->getConfigFieldModel(
            Product::class,
            OroFrontendTestFrameworkBundleInstaller::VARIANT_FIELD_NAME
        );

        $attributeGroupRelation = new AttributeGroupRelation();
        $attributeGroupRelation->setEntityConfigFieldId($variantField->getId());
        $attributeGroup->addAttributeRelation($attributeGroupRelation);
        $manager->persist($defaultFamily);
        $manager->flush();
    }
}
