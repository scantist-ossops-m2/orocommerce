<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Form\EventSubscriber\DigitalAssetTwigTagsEventSubscriber;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Symfony\Component\Asset\Packages as AssetHelper;

/**
 * @method \PHPUnit\Framework\MockObject\MockObject createMock(string $originalClassName)
 */
trait WysiwygAwareTestTrait
{
    private function createWysiwygType(): WYSIWYGType
    {
        /** @var HtmlTagProvider|\PHPUnit\Framework\MockObject\MockObject $htmlTagProvider */
        $htmlTagProvider = $this->createMock(HtmlTagProvider::class);
        /** @var HTMLPurifierScopeProvider|\PHPUnit\Framework\MockObject\MockObject $purifierScopeProvider */
        $purifierScopeProvider = $this->createMock(HTMLPurifierScopeProvider::class);
        /** @var DigitalAssetTwigTagsConverter|\PHPUnit\Framework\MockObject\MockObject $twigTagsConverter */
        $twigTagsConverter = $this->createMock(DigitalAssetTwigTagsConverter::class);
        $twigTagsConverter
            ->method('convertToUrls')
            ->willReturnArgument(0);
        $twigTagsConverter
            ->method('convertToTwigTags')
            ->willReturnArgument(0);
        $assetHelper = $this->createMock(AssetHelper::class);
        $assetHelper
            ->expects(self::any())
            ->method('getUrl')
            ->willReturnArgument(0);

        $eventSubscriber = new DigitalAssetTwigTagsEventSubscriber($twigTagsConverter);

        return new WYSIWYGType($htmlTagProvider, $purifierScopeProvider, $eventSubscriber, $assetHelper);
    }
}