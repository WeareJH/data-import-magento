<?php

namespace Jh\DataImportTest\ValueConverter;

use Jh\DataImportMagento\ValueConverter\AttributeOptionValueConverter;

/**
 * Class AttributeOptionValueConverter
 * @package Jh\DataImportTest\ValueConverter
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class AttributeOptionValueConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AttributeOptionValueConverter
     */
    protected $converter = null;

    public function setUp()
    {
        $attributeCode = 'colour';
        $options = array(
            '1' => 'Red',
            '2' => 'Purple',
            '3' => 'Orange',
            '4' => 'Green',
        );

        $this->converter = new AttributeOptionValueConverter($attributeCode, $options);
    }

    public function testConverterReturnsCorrectValueForOptionKey()
    {
        $this->assertEquals('Purple', $this->converter->convert(2));
    }

    public function testConverterThrowsExceptionIfKeyNotExists()
    {
        $this->setExpectedException(
            'Ddeboer\DataImport\Exception\UnexpectedValueException',
            '"6" does not appear to be a valid attribute option for "colour"'
        );

        $this->converter->convert(6);
    }
}
