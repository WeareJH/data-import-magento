<?php

namespace Jh\DataImportTest\ValueConverter;

use Jh\DataImportMagento\ValueConverter\UcwordsValueConverter;

/**
 * Class UcwordsValueConverterTest
 * @package Jh\DataImportTest\ValueConverter
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class UcwordsValueConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UcwordsValueConverter
     */
    protected $converter = null;

    public function setUp()
    {
        $this->converter = new UcwordsValueConverter();
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
        $this->assertSame(
            "All These Words Should Begin With A Capital Letter",
            $this->converter->convert('all these words should begin with a capital letter')
        );
    }
}
