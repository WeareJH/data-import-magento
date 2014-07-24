<?php

namespace Jh\DataImportmagentoTest\ItemConverter;

use Jh\DataImportMagento\ItemConverter\RemoveUnwantedFieldsConverter;
use MyProject\Proxies\__CG__\stdClass;

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

        $fieldsToKeep = array('keepMe1', 'keepMe2');
        $converter = new RemoveUnwantedFieldsConverter($fieldsToKeep);

        $output = $converter->convert($input);

        $expected = array(
            'keepMe1' => 'foo',
            'keepMe2' => 'bar',
        );
        $this->assertEquals($expected, $output);
    }

    public function testNestedFieldsConvert()
    {
        $input = [
            'foo'       => 'bar',
            'keepMe1'   => 'foo',
            'items' => [
                [
                    'one'       => 'one',
                    'two'       => 'two',
                    'notNeeded' => 'deleteMe',
                ],
                [
                    'one'                   => 'one',
                    'two'                   => 'two',
                    'doNotNeedThisEither'   => 'deleteMe'
                ]
            ]
        ];

        $fieldsToKeep = [
            'keepMe1',
            'items' => [
                'one',
                'two'
            ],
        ];
        $converter = new RemoveUnwantedFieldsConverter($fieldsToKeep);

        $expected = [
            'keepMe1'   => 'foo',
            'items' => [
                [
                    'one'   => 'one',
                    'two'   => 'two',
                ],
                [
                    'one'   => 'one',
                    'two'   => 'two',
                ]
            ]
        ];

        $output = $converter->convert($input);
        $this->assertEquals($expected, $output);
    }

    public function testConverterThrowsExceptionIfInputNotArray()
    {
        $converter = new RemoveUnwantedFieldsConverter([]);
        $this->setExpectedException(
            'Ddeboer\DataImport\Exception\UnexpectedTypeException',
            'Expected argument of type "array", "stdClass" given'
        );
        $converter->convert(new \stdClass());
    }

    public function testNestedItemWhichDoesNotExistInInputDataIsReplacedWithEmptyArray()
    {
        $fieldsToKeep = [
            'items' => [
                'one',
            ],
        ];
        $converter  = new RemoveUnwantedFieldsConverter($fieldsToKeep);
        $input      = [];

        $this->assertSame(['items' => []], $converter->convert($input));
    }

    public function testExceptionIsThrownIfNestDataIsNotAnArray()
    {
        $fieldsToKeep = [
            'items' => [
                'one',
            ],
        ];

        $converter  = new RemoveUnwantedFieldsConverter($fieldsToKeep);
        $input      = [
            'items' => new \stdClass
        ];

        $this->setExpectedException(
            'Ddeboer\DataImport\Exception\UnexpectedTypeException',
            'Expected argument of type "array", "stdClass" given'
        );
        $converter->convert($input);
    }

    public function testNestedItemIsPopulatedWithDefaultValueIfRequiredFieldDoesNotExist()
    {
        $fieldsToKeep = [
            'items' => [
                'one',
            ],
        ];
        $converter  = new RemoveUnwantedFieldsConverter($fieldsToKeep);

        $input = [
            'items' => [
                []
            ]
        ];

        $expected = [
            'items' => [
                ['one' => '']
            ]
        ];

        $this->assertSame($expected, $converter->convert($input));
    }

    public function testFirstLevelDataIsPopulatedWithDefaultValueIfRequiredFieldDoesNotExist()
    {
        $fieldsToKeep = [
            'name'
        ];
        $converter  = new RemoveUnwantedFieldsConverter($fieldsToKeep);

        $input = [];

        $expected = [
            'name' => ''
        ];

        $this->assertSame($expected, $converter->convert($input));
    }
}
