<?php

namespace Jh\DataImportTest\ValueConverter;

use Jh\DataImportMagento\ValueConverter\StrtolowerValueConverter;

/**
 * Class StrtolowerValueConverterTest
 * @package Jh\DataImportTest\ValueConverter
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class StrtolowerValueConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StrtolowerValueConverter
     */
    protected $converter = null;

    public function setUp()
    {
        $this->converter = new StrtolowerValueConverter();
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
        $this->assertSame("lowercase", $this->converter->convert('LOWERCASE'));
    }
}
