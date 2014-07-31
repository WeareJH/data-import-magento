<?php

namespace Ddeboer\DataImport\Tests;

use Jh\DataImportMagento\NestedValueConverterWorkflow;
use Ddeboer\DataImport\Reader\ArrayReader;
use Ddeboer\DataImport\ValueConverter\CallbackValueConverter;

class NestedValueConverterWorkflowTest extends \PHPUnit_Framework_TestCase
{
    public function testCanAddSameValueConvertToMultipleFields()
    {
        $workflow       = $this->getWorkflow();
        $valueConverter = new CallbackValueConverter(function () {
        });

        $workflow->addValueConverter(
            [
                'first',
                'last',
            ],
            $valueConverter
        );

        $refObject   = new \ReflectionObject($workflow);
        $refProperty = $refObject->getProperty('valueConverters');
        $refProperty->setAccessible(true);
        $converters = $refProperty->getValue($workflow);

        $this->assertCount(2, $converters);
        $this->assertCount(1, $converters['first']);
        $this->assertCount(1, $converters['last']);
        $this->assertSame($valueConverter, $converters['first'][0]);
        $this->assertSame($valueConverter, $converters['last'][0]);
    }

    public function testCanAddValueConverterToOneField()
    {
        $workflow       = $this->getWorkflow();
        $valueConverter = new CallbackValueConverter(function () {
        });

        $workflow->addValueConverter(
            'first',
            $valueConverter
        );

        $refObject   = new \ReflectionObject($workflow);
        $refProperty = $refObject->getProperty('valueConverters');
        $refProperty->setAccessible(true);
        $converters = $refProperty->getValue($workflow);

        $this->assertCount(1, $converters);
        $this->assertCount(1, $converters['first']);
        $this->assertSame($valueConverter, $converters['first'][0]);
    }

    public function testValueConverterOnNonNestedProperties()
    {
        $workflow       = $this->getWorkflow();
        $valueConverter = new CallbackValueConverter(function () {
            return 'convertedValue';
        });

        $workflow->addValueConverter(
            'first',
            $valueConverter
        );

        $method = new \ReflectionMethod($workflow, 'convertItem');
        $method->setAccessible(true);

        $data = [
            'first' => 'James',
            'last'  => 'Bond'
        ];

        $convertedItem = $method->invoke($workflow, $data);

        $expected = [
            'first' => 'convertedValue',
            'last'  => 'Bond'
        ];

        $this->assertSame($expected, $convertedItem);
    }

    public function testValueConverterOnNestedProperties()
    {
        $workflow       = $this->getWorkflow();
        $valueConverter = new CallbackValueConverter(function () {
            return 'convertedValue';
        });

        $workflow->addValueConverter(
            [
                'name/first',
                'name/last',
            ],
            $valueConverter
        );

        $method = new \ReflectionMethod($workflow, 'convertItem');
        $method->setAccessible(true);

        $data = [
            'name' => [
                'first' => 'James',
                'last'  => 'Bond'
            ]
        ];

        $convertedItem = $method->invoke($workflow, $data);

        $expected = [
            'name' => [
                'first' => 'convertedValue',
                'last'  => 'convertedValue'
            ]
        ];

        $this->assertSame($expected, $convertedItem);
    }

    public function testValueConverterOnNestedArray()
    {
        $workflow       = $this->getWorkflow();
        $valueConverter = new CallbackValueConverter(function () {
            return 'convertedValue';
        });

        $workflow->addValueConverter(
            [
                'name/first',
                'name/last',
            ],
            $valueConverter
        );

        $method = new \ReflectionMethod($workflow, 'convertItem');
        $method->setAccessible(true);

        $data = [
            'name' => [
                [
                    'first' => 'James',
                    'last'  => 'Bond'
                ],
                [
                    'first' => 'Miss',
                    'last'  => 'Moneypenny'
                ],
            ]
        ];

        $convertedItem = $method->invoke($workflow, $data);

        $expected = [
            'name' => [
                [
                    'first' => 'convertedValue',
                    'last'  => 'convertedValue'
                ],
                [
                    'first' => 'convertedValue',
                    'last'  => 'convertedValue'
                ],
            ]
        ];

        $this->assertSame($expected, $convertedItem);
    }

    public function testValueConverterIgnoresKeyStructureWhichDoesNotExist()
    {
        $workflow       = $this->getWorkflow();
        $valueConverter = new CallbackValueConverter(function () {
            return 'convertedValue';
        });

        $workflow->addValueConverter(
            [
                'name/nothere',
            ],
            $valueConverter
        );

        $method = new \ReflectionMethod($workflow, 'convertItem');
        $method->setAccessible(true);

        $data = [
            'name' => [
                [
                    'first' => 'James',
                    'last'  => 'Bond'
                ],
                [
                    'first' => 'Miss',
                    'last'  => 'Moneypenny'
                ],
            ]
        ];

        $convertedItem = $method->invoke($workflow, $data);

        $expected = [
            'name' => [
                [
                    'first' => 'James',
                    'last'  => 'Bond'
                ],
                [
                    'first' => 'Miss',
                    'last'  => 'Moneypenny'
                ],
            ]
        ];

        $this->assertSame($expected, $convertedItem);
    }

    public function testValueConverterIgnoresKeyStructureWhichDoesNotExist2()
    {
        $workflow       = $this->getWorkflow();
        $valueConverter = new CallbackValueConverter(function () {
            return 'convertedValue';
        });

        $workflow->addValueConverter(
            [
                'nothere/nothere',
            ],
            $valueConverter
        );

        $method = new \ReflectionMethod($workflow, 'convertItem');
        $method->setAccessible(true);

        $data = [
            'name' => [
                [
                    'first' => 'James',
                    'last'  => 'Bond'
                ],
                [
                    'first' => 'Miss',
                    'last'  => 'Moneypenny'
                ],
            ]
        ];

        $convertedItem = $method->invoke($workflow, $data);

        $expected = [
            'name' => [
                [
                    'first' => 'James',
                    'last'  => 'Bond'
                ],
                [
                    'first' => 'Miss',
                    'last'  => 'Moneypenny'
                ],
            ]
        ];

        $this->assertSame($expected, $convertedItem);
    }

    public function testValueConverterOnDoubleNestedProperties()
    {
        $workflow       = $this->getWorkflow();
        $valueConverter = new CallbackValueConverter(function () {
            return 'convertedValue';
        });

        $workflow->addValueConverter(
            [
                'address/street/street1',
                'address/street/street2',
            ],
            $valueConverter
        );

        $method = new \ReflectionMethod($workflow, 'convertItem');
        $method->setAccessible(true);

        $data = [
            'address' => [
                'street' => [
                    'street1' => '61 Horsen Ferry Road',
                    'street2' => 'London'
                ]
            ]
        ];

        $convertedItem = $method->invoke($workflow, $data);

        $expected = [
            'address' => [
                'street' => [
                    'street1' => 'convertedValue',
                    'street2' => 'convertedValue'
                ]
            ]
        ];

        $this->assertSame($expected, $convertedItem);
    }

    public function testValueConverterOnDoubleNestedArrayProperties()
    {
        $workflow       = $this->getWorkflow();
        $valueConverter = new CallbackValueConverter(function () {
            return 'convertedValue';
        });

        $workflow->addValueConverter(
            [
                'addresses/street/street1',
                'addresses/street/street2',
            ],
            $valueConverter
        );

        $method = new \ReflectionMethod($workflow, 'convertItem');
        $method->setAccessible(true);

        $data = [
            'addresses' => [
                [
                    'street' => [
                        'street1' => '61 Horsen Ferry Road',
                        'street2' => 'London'
                    ],
                ],
                [
                    'street' => [
                        'street1' => '62 Horsen Ferry Road',
                        'street2' => 'London'
                    ],
                ],
            ]
        ];

        $convertedItem = $method->invoke($workflow, $data);

        $expected = [
            'addresses' => [
                [
                    'street' => [
                        'street1' => 'convertedValue',
                        'street2' => 'convertedValue'
                    ],
                ],
                [
                    'street' => [
                        'street1' => 'convertedValue',
                        'street2' => 'convertedValue'
                    ],
                ],
            ]
        ];

        $this->assertSame($expected, $convertedItem);
    }

    protected function getWorkflow()
    {
        $reader = new ArrayReader(array(
            array(
                'first' => 'James',
                'last'  => 'Bond'
            ),
            array(
                'first' => 'Miss',
                'last'  => 'Moneypenny'
            ),
            array(
                'first' => null,
                'last'  => 'Doe'
            )
        ));

        return new NestedValueConverterWorkflow($reader);
    }
}
