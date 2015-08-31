<?php

namespace Jh\DataImportTest\ValueConverter;

use Jh\DataImportMagento\ValueConverter\ProductStatusValueConverter;

/**
 * Class ProductStatusValueConverterTest
 * @package Jh\DataImportTest\ValueConverter
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class ProductStatusValueConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductStatusValueConverter
     */
    protected $converter;

    public function setup()
    {
        $this->converter = new ProductStatusValueConverter;
    }

    public function testExceptionIsThrownIfInvalidStatusIsPassed()
    {
        $message  = 'Given Product Status: "on-vacation" is not valid. Allowed values: ';
        $message .= '"Enabled", "Disabled"';

        $this->setExpectedException('\Ddeboer\DataImport\Exception\UnexpectedValueException', $message);
        $this->converter->convert('on-vacation');
    }

    public function testConvert()
    {
        $this->assertEquals(1, $this->converter->convert('Enabled'));
    }

    public function testDefaultValueIsUsedIfNoValueSet()
    {
        $this->assertEquals(2, $this->converter->convert(""));
    }
}
