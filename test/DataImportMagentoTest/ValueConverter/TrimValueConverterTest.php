<?php

namespace Jh\DataImportTest\ValueConverter;

use Jh\DataImportMagento\ValueConverter\TrimValueConverter;

/**
 * Class TrimValueConverterTest
 * @package Jh\DataImportTest\ValueConverter
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class TrimValueConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TrimValueConverter
     */
    protected $converter = null;

    public function setUp()
    {
        $this->converter = new TrimValueConverter();
    }

    public function testConverterThrowsExceptionIfValueIsNotString()
    {
        $this->setExpectedException(
            'Ddeboer\DataImport\Exception\UnexpectedTypeException',
            'Expected argument of type "string", "stdClass" given'
        );

        $this->converter->convert(new \stdClass);
    }

    public function testTrim()
    {
        $this->assertEquals('lol', $this->converter->convert('    lol    '));
    }

    public function testTrimWithCharacterMask()
    {
        $this->converter = new TrimValueConverter('l');
        $this->assertEquals('ol!', $this->converter->convert('lol!'));
    }
}
