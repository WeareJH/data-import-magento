<?php

namespace Jh\DataImportTest\ValueConverter;

use Jh\DataImportMagento\ValueConverter\ProductVisibilityValueConverter;

/**
 * Class ProductVisibilityValueConverterTest
 * @package Jh\DataImportTest\ValueConverter
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class ProductVisibilityValueConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductVisibilityValueConverter
     */
    protected $converter;

    public function setup()
    {
        $this->converter = new ProductVisibilityValueConverter;
    }

    public function testExceptionIsThrownIfInvalidVisibilityIsPassed()
    {
        $message  = 'Given Product Visibility: "illusive" is not valid. Allowed values: ';
        $message .= '"Not Visible Individually", "Catalog", "Search", "Catalog, Search"';

        $this->setExpectedException('\Ddeboer\DataImport\Exception\UnexpectedValueException', $message);
        $this->converter->convert('illusive');
    }

    public function testConvert()
    {
        $this->assertEquals(4, $this->converter->convert('Catalog, Search'));
    }
}
