<?php

namespace Jh\DataImportTest\ValueConverter;

use Jh\DataImportMagento\ValueConverter\DateTimeFormatterValueConverter;
/**
 * Class DateTimeFormatterValueConverterTest
 * @package Jh\DataImportTest\ValueConverter
 * @author Aydin Hassan <aydin@hotmail.co.uk.com>
 */
class DateTimeFormatterValueConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testConvertWithoutInputOrOutputFormatReturnsDateTimeInstance()
    {
        $value = '2011-10-20 13:05';
        $converter = new DateTimeFormatterValueConverter;
        $output = $converter->convert($value);
        $this->assertInstanceOf('\DateTime', $output);
        $this->assertEquals('13', $output->format('H'));
    }

    public function testConvertWithFormatReturnsDateTimeInstance()
    {
        $value = '14/10/2008 09:40:20';
        $converter = new DateTimeFormatterValueConverter('d/m/Y H:i:s');
        $output = $converter->convert($value);
        $this->assertInstanceOf('\DateTime', $output);
        $this->assertEquals('20', $output->format('s'));
    }

    public function testConvertWithInputAndOutputFormatReturnsString()
    {
        $value = '14/10/2008 09:40:20';
        $converter = new DateTimeFormatterValueConverter('d/m/Y H:i:s', 'd-M-Y');
        $output = $converter->convert($value);
        $this->assertEquals('14-Oct-2008', $output);
    }

    public function testConvertWithNoInputStringWithOutputFormatReturnsString()
    {
        $value = '2011-10-20 13:05';
        $converter = new DateTimeFormatterValueConverter(null, 'd-M-Y');
        $output = $converter->convert($value);
        $this->assertEquals('20-Oct-2011', $output);

    }

    public function testInvalidInputFormatThrowsException()
    {
        $value = '14/10/2008 09:40:20';
        $converter = new DateTimeFormatterValueConverter('d-m-y', 'd-M-Y');
        $this->setExpectedException("UnexpectedValueException", "14/10/2008 09:40:20 is not a valid date/time according to format d-m-y");
        $converter->convert($value);
    }


}

