<?php

namespace Jh\DataImportTest\Reader;



use Jh\DataImportMagento\Reader\XmlReader;
use Symfony\Component\Config\Definition\Exception\Exception;

class XmlReaderTest extends \PHPUnit_Framework_TestCase
{
    protected $reader;

    public function setUp()
    {
    }

    public function testValidXmlCanBeParsedTrue()
    {
        $file = new \SplFileObject(__DIR__ . '/../Fixtures/valid_xml.xml');
        $this->reader = new XmlReader($file);
    }

    public function testInvalidXmlThrowsException()
    {
        $file = new \SplFileObject(__DIR__ . '/../Fixtures/invalid_xml.xml');

        $expectedMessage = "Failed to parse file. Errors: 'Opening and ending tag mismatch: orderStatus line 2"
         . " and order', 'Extra content at the end of the document'";

        $this->setExpectedException('Ddeboer\DataImport\Exception\ReaderException', $expectedMessage);
        $this->reader = new XmlReader($file);
    }

    public function testStructureOfDecodedXmlIsValid()
    {
        $file = new \SplFileObject(__DIR__ . '/../Fixtures/valid_xml.xml');
        $this->reader = new XmlReader($file);

        $data = $this->reader->current();

        $expected = array (
            'order' => array (
                'clientCode'            => '511',
                'orderNumber'           => 'OR0000008',
                'customerOrderNumber'   => '323424324234',
                'statuses'              => array (
                    'status' => array (
                        array (
                            'statusCode'        => 'APK',
                            'statusDescription' => 'Awaiting Picking',
                            'statusDate'        => '2014-06-24T13:14:51.000Z',
                            'userId'            => 'STEVE',
                            'documentReference' => array (),
                            'statusAction'      => 'Added',
                        ),
                        array (
                            'statusCode'        => 'PA',
                            'statusDescription' => 'Part Allocated',
                            'statusDate'        => '2014-06-24T13:14:51.000Z',
                            'userId'            => array (),
                            'documentReference' => array (),
                            'statusAction'      => 'Added',
                        ),
                    ),
                ),
                'lines' => array (
                    'line' => array (
                        array (
                            'rucLineId'         => array (),
                            'lineNumber'        => '1',
                            'priamSku'          => '2000122108026',
                            'qtyRequired'       => '1',
                            'qtyAllocated'      => '1',
                            'qtyDespatched'     => '0',
                            'qtyCancelled'      => '0',
                            'qtyLost'           => '0',
                        ),
                        array (
                            'rucLineId'         => array (),
                            'lineNumber'        => '2',
                            'priamSku'          => '2000122108033',
                            'qtyRequired'       => '1',
                            'qtyAllocated'      => '0',
                            'qtyDespatched'     => '0',
                            'qtyCancelled'      => '0',
                            'qtyLost'           => '0',
                        ),
                    ),
                ),
                'packages'      => array (),
                'userId'        => 'STEVE',
                'userFullName'  => 'STEVE GLAZE',
            ),
        );

        $this->assertEquals($expected, $data);
    }

    public function testGetFieldsReturnsTopLevelNodes()
    {
        $file = new \SplFileObject(__DIR__ . '/../Fixtures/valid_xml.xml');
        $this->reader = new XmlReader($file);

        $this->assertEquals(array('order'), $this->reader->getFields());
    }

    public function testCountReturnsOne()
    {
        $file = new \SplFileObject(__DIR__ . '/../Fixtures/valid_xml.xml');
        $this->reader = new XmlReader($file);

        $this->assertEquals(1, $this->reader->count());
    }

    public function testKeyIsAlwaysZero()
    {
        $file = new \SplFileObject(__DIR__ . '/../Fixtures/valid_xml.xml');
        $this->reader = new XmlReader($file);

        $this->assertEquals(0, $this->reader->key());
    }

    public function testValidFunctionReturnsFalseAfterFirstCallOfCurrent()
    {
        $file = new \SplFileObject(__DIR__ . '/../Fixtures/valid_xml.xml');
        $this->reader = new XmlReader($file);
        $this->assertTrue($this->reader->valid());
        $this->reader->current();
        $this->assertFalse($this->reader->valid());
    }
}
