<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\ShippingBundle\Formatter\DimensionsValueFormatter;
use Oro\Bundle\ShippingBundle\Model\DimensionsValue;
use Symfony\Contracts\Translation\TranslatorInterface;

class DimensionsValueFormatterTest extends \PHPUnit\Framework\TestCase
{
    const TRANSLATION_PREFIX = 'oro.length_unit';

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $numberFormatter;

    /** @var DimensionsValueFormatter */
    protected $formatter;

    protected function setUp(): void
    {
        $this->translator = $this->createMock('Symfony\Contracts\Translation\TranslatorInterface');
        $this->numberFormatter = $this->createMock(NumberFormatter::class);

        $this->formatter = new DimensionsValueFormatter($this->translator, $this->numberFormatter);
        $this->formatter->setTranslationPrefix(self::TRANSLATION_PREFIX);
    }

    protected function tearDown(): void
    {
        unset($this->formatter, $this->translator);
    }

    public function testFormatCodeWithScientificNotation(): void
    {
        $this->configureFormatter(6e-6, '0.000006', [\NumberFormatter::FRACTION_DIGITS => PHP_FLOAT_DIG]);
        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap(
                [
                    ['N/A', [], null, null, 'N/A_trans'],
                    [static::TRANSLATION_PREFIX . '.item.label.short', [], null, null, 'translated']
                ]
            );

        $this->assertEquals(
            '0.000006 x 0.000006 x 0.000006 translated',
            $this->formatter->formatCode(DimensionsValue::create(6e-6, 6e-6, 6e-6), 'item', true)
        );
    }

    public function testFormatCodeShort()
    {
        $this->configureFormatter(42, 42, \NumberFormatter::TYPE_DEFAULT);
        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap(
                [
                    ['N/A', [], null, null, 'N/A_trans'],
                    [static::TRANSLATION_PREFIX . '.item.label.short', [], null, null, 'translated']
                ]
            );

        $this->assertEquals(
            '42 x 42 x 42 translated',
            $this->formatter->formatCode(DimensionsValue::create(42, 42, 42), 'item', true)
        );
    }

    public function testFormatCodeFull()
    {
        $this->configureFormatter(42, 42, \NumberFormatter::TYPE_DEFAULT);

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap(
                [
                    ['N/A', [], null, null, 'N/A_trans'],
                    [static::TRANSLATION_PREFIX . '.item.label.full', [], null, null, 'translated']
                ]
            );

        $this->assertEquals(
            '42 x 42 x 42 translated',
            $this->formatter->formatCode(DimensionsValue::create(42, 42, 42), 'item')
        );
    }

    public function testFormatCodeNullValue()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('N/A', [], null, null)
            ->willReturn('N/A_trans');

        $this->assertEquals(
            'N/A_trans',
            $this->formatter->formatCode(null, 'item')
        );
    }

    public function testFormatCodeEmptyValue()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('N/A', [], null, null)
            ->willReturn('N/A_trans');

        $this->assertEquals(
            'N/A_trans',
            $this->formatter->formatCode(DimensionsValue::create(null, null, null), 'item')
        );
    }

    public function testFormatCodeEmptyCode()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('N/A', [], null, null)
            ->willReturn('N/A_trans');

        $this->assertEquals(
            'N/A_trans',
            $this->formatter->formatCode(DimensionsValue::create(42, 42, 42), null)
        );
    }

    /**
     * @param float $inputNumber
     * @param string|float $outputNumber
     * @param mixed $attributes
     */
    protected function configureFormatter($inputNumber, $outputNumber, $attributes): void
    {
        $method = is_int($inputNumber) ? 'format' : 'formatDecimal';
        $this->numberFormatter
            // length, width, height.
            ->expects($this->exactly(3))
            ->method($method)
            ->with($inputNumber, $attributes)
            ->willReturn($outputNumber);
    }
}
