<?php

namespace Jh\DataImportTest\ValueConverter;

use AspectMock\Proxy\InstanceProxy;
use AspectMock\Test;
use Jh\DataImportMagento\ValueConverter\TaxClassValueConverter;

/**
 * Class TaxClassValueConverterTest
 * @package Jh\DataImportTest\ValueConverter
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class TaxClassValueConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TaxClassValueConverter
     */
    protected $converter;

    /**
     * @var InstanceProxy
     */
    protected $mageDouble;

    public function setup()
    {
        $taxClassSourceProduct = $this->getMockBuilder('\Mage_Tax_Model_Class_Source_Product')
            ->disableOriginalConstructor()
            ->setMethods(['getAllOptions'])
            ->getMock();

        $taxClassSourceProduct
            ->expects($this->once())
            ->method('getAllOptions')
            ->will($this->returnValue([
                ['value' => 2, 'label' => 'Taxable Goods'],
                ['value' => 4, 'label' => 'Shipping'],
            ]));

        $this->mageDouble = Test::double('Mage', ['getSingleton' => $taxClassSourceProduct]);
        $this->converter = new TaxClassValueConverter;
    }

    public function testExceptionIsThrownIfInvalidTaxClassIsPassed()
    {
        $message  = 'Given Tax-Class: "no-tax-yeah-right" is not valid. Allowed values: ';
        $message .= '"Taxable Goods", "Shipping"';

        $this->setExpectedException('\Ddeboer\DataImport\Exception\UnexpectedValueException', $message);
        $this->converter->convert('no-tax-yeah-right');
        $this->mageDouble->verifyInvoked('getSingleton', ['tax/class_source_product']);
    }

    public function testConvert()
    {
        $this->assertEquals(2, $this->converter->convert('Taxable Goods'));
        $this->mageDouble->verifyInvoked('getSingleton', ['tax/class_source_product']);
    }

    public function testDefaultValueIsUsedIfNoValueSet()
    {
        $this->assertEquals(2, $this->converter->convert(""));
        $this->mageDouble->verifyInvoked('getSingleton', ['tax/class_source_product']);
    }
}
