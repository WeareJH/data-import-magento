<?php

namespace Jh\DataImportMagentoTest\ItemConverter;

use Jh\DataImportMagento\ItemConverter\ItemNesterConverter;

/**
 * Class ItemNestConverterTest
 * @package Jh\DataImportMagentoTest\ItemConverter
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ItemNestConverterTest extends \PHPUnit_Framework_TestCase
{

    public function testSetMappingAccepts1DimensionalArrayOfMappings()
    {

        $mappings = array(
            'nestMe1',
            'nestMe2'
        );

        $itemConvert = $this->getMockBuilder('Jh\DataImportMagento\ItemConverter\ItemNesterConverter')
                            ->disableOriginalConstructor()
                            ->setMethods(array('__construct'))
                            ->getMock();

        $itemConvert->setMappings($mappings);

        $expected = array(
            'nestMe1' => true,
            'nestMe2' => true,
        );
        $this->assertEquals($expected, $itemConvert->getMappings());
    }

    public function testSetMappingAccepts2DimensionalArrayOfMappings()
    {

        $mappings = array(
            array('nestMe1', false),
            array('nestMe2', true),
        );

        $itemConvert = $this->getMockBuilder('Jh\DataImportMagento\ItemConverter\ItemNesterConverter')
            ->disableOriginalConstructor()
            ->setMethods(array('__construct'))
            ->getMock();

        $itemConvert->setMappings($mappings);

        $expected = array(
            'nestMe1' => false,
            'nestMe2' => true,
        );
        $this->assertEquals($expected, $itemConvert->getMappings());
    }

    public function testSetMappingsThrowsExceptionIfRemoveArgumentNotBoolean()
    {
        $mappings = array(
            array('nestMe1', new \stdClass),
            array('nestMe2', true),
        );

        $itemConvert = $this->getMockBuilder('Jh\DataImportMagento\ItemConverter\ItemNesterConverter')
            ->disableOriginalConstructor()
            ->setMethods(array('__construct'))
            ->getMock();

        $this->setExpectedException(
            'InvalidArgumentException',
            'Second Argument should be an boolean value - whether to remove the value from parent row'
        );
        $itemConvert->setMappings($mappings);
    }

    public function testSetMappingAcceptsBoth2dAnd3dMappings()
    {

        $mappings = array(
            array('nestMe1', false),
            'nestMe2',
        );

        $itemConvert = $this->getMockBuilder('Jh\DataImportMagento\ItemConverter\ItemNesterConverter')
            ->disableOriginalConstructor()
            ->setMethods(array('__construct'))
            ->getMock();

        $itemConvert->setMappings($mappings);

        $expected = array(
            'nestMe1' => false,
            'nestMe2' => true,
        );
        $this->assertEquals($expected, $itemConvert->getMappings());
    }

    public function testDataIsTransformedCorrectly()
    {
        $mappings = array(
            array('nestMe1', false),
            array('nestMe2', true),
        );

        $input = array(
            'nestMe1'       => 'someValue1',
            'nestMe2'       => 'someValue2',
            'leaveMeHere'   => 'someValue3',
        );

        $expected = array(
            'nestMe1'       => 'someValue1',
            'leaveMeHere'   => 'someValue3',
            'nested'        => array(
                array(
                    'nestMe1'       => 'someValue1',
                    'nestMe2'       => 'someValue2',
                )
            )
        );

        $itemConvert = new ItemNesterConverter($mappings, 'nested');
        $output = $itemConvert->convert($input);

        $this->assertEquals($expected, $output);
    }
}