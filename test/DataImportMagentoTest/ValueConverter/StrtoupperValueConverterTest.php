<?php

namespace Jh\DataImportTest\ValueConverter;

use Jh\DataImportMagento\ValueConverter\StrtoupperValueConverter;

/**
 * Class StrtoupperValueConverterTest
 * @package Jh\DataImportTest\ValueConverter
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class StrtoupperValueConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StrtoupperValueConverter
     */
    protected $converter = null;

    public function setUp()
    {
        $this->converter = new StrtoupperValueConverter();
    }

    public function testConverterThrowsExceptionIfValueIsNotString()
    {
        $this->setExpectedException(
            'Ddeboer\DataImport\Exception\UnexpectedTypeException',
            'Expected argument of type "string", "stdClass" given'
        );

        $this->converter->convert(new \stdClass);
    }

    public function testConverterUpperCasesStringInput()
    {
        $this->assertSame("UPPERCASE", $this->converter->convert('uppercase'));
    }
}
