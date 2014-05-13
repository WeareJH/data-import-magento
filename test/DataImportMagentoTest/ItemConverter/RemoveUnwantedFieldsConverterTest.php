<?php

namespace Jh\DataImportmagentoTest\ItemConverter;

use Jh\DataImportMagento\ItemConverter\RemoveUnwantedFieldsConverter;

/**
 * Class RemoveUnwantedFieldsConverterTest
 * @package Ddeboer\DataImport\Tests\ItemConverter
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class RemoveUnwantedFieldsConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testConvert()
    {
        $input = array(
            'foo' => 'bar',
            'keepMe1' => 'foo',
            'keepMe2' => 'bar',
        );

        $fieldsTokeep = array('keepMe1', 'keepMe2');
        $converter = new RemoveUnwantedFieldsConverter($fieldsTokeep);

        $output = $converter->convert($input);

        $expected = array(
            'keepMe1' => 'foo',
            'keepMe2' => 'bar',
        );
        $this->assertEquals($expected, $output);
    }
}