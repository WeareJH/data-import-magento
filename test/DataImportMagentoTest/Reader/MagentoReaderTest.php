<?php

namespace Jh\DataImportTest\Reader;

use Jh\DataImportMagento\Reader\MagentoReader;

/**
 * Class MagentoReaderTest
 * @package Jh\DataImportTest\ValueConverter
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class MagentoReaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MagentoReader
     */
    protected $reader = null;

    protected $collection = null;

    protected $select = null;

    public function setUp()
    {
        $this->collection = $this
            ->getMockBuilder('\Mage_Core_Model_Resource_Db_Collection_Abstract')
            ->disableOriginalConstructor()
            ->getMock();

        $this->select = $this
            ->getMockBuilder('\Varien_Db_Select')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetFields()
    {
        $this->collection
            ->expects($this->once())
            ->method('getSelect')
            ->will($this->returnValue($this->select));

        $statement = $this->getMock('\Zend_Db_Statement_Interface');
        $this->select
            ->expects($this->once())
            ->method('query')
            ->will($this->returnValue($statement));

        $this->reader = new MagentoReader($this->collection);

        $statement->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue(array('one' => 1, 'two' => 2, 'three' => 3)));

        $this->assertEquals(array('one', 'two', 'three'), $this->reader->getFields());
    }

    public function testGetCountReturnsCollectionSize()
    {

        $this->collection
            ->expects($this->once())
            ->method('getSelect')
            ->will($this->returnValue($this->select));

        $this->reader = new MagentoReader($this->collection);

        $this->collection
            ->expects($this->once())
            ->method('getSize')
            ->will($this->returnValue(5));

        $this->assertEquals(5, $this->reader->count());
    }

    public function testRewindGetsNewQueryAndIndexIsReset()
    {
        $this->collection
            ->expects($this->once())
            ->method('getSelect')
            ->will($this->returnValue($this->select));

        $statement1 = $this->getMock('\Zend_Db_Statement_Interface');
        $statement2 = $this->getMock('\Zend_Db_Statement_Interface');
        $this->select
            ->expects($this->at(0))
            ->method('query')
            ->will($this->returnValue($statement1));

        $this->select
            ->expects($this->at(1))
            ->method('query')
            ->will($this->returnValue($statement2));

        $this->reader = new MagentoReader($this->collection);

        $statement1
            ->expects($this->exactly(2))
            ->method('fetch');

        $statement2
            ->expects($this->once())
            ->method('fetch');

        $this->reader->rewind();
        $this->assertEquals(1, $this->reader->key());
        $this->reader->next();
        $this->assertEquals(2, $this->reader->key());
        $this->reader->rewind();
        $this->assertEquals(1, $this->reader->key());
    }

    public function testIterator()
    {
        $this->collection
            ->expects($this->once())
            ->method('getSelect')
            ->will($this->returnValue($this->select));

        $this->reader = new MagentoReader($this->collection);
        $statement = $this->getMock('\Zend_Db_Statement_Interface');
        $this->select
            ->expects($this->once())
            ->method('query')
            ->will($this->returnValue($statement));

        $data = array(
            array('one' => 1, 'two' => 2, 'three' => 3),
            array('one' => 11, 'two' => 22, 'three' => 33),
            array('one' => 111, 'two' => 222, 'three' => 333),
        );

        $statement->expects($this->at(0))
            ->method('fetch')
            ->will($this->returnValue($data[0]));

        $statement->expects($this->at(1))
            ->method('fetch')
            ->will($this->returnValue($data[1]));

        $statement->expects($this->at(2))
            ->method('fetch')
            ->will($this->returnValue($data[2]));

        $i = 1;
        foreach ($this->reader as $key => $row) {
            $this->assertEquals($i, $key);
            $this->assertEquals($data[$i - 1], $row);
            $i++;
        }
    }

    public function testReaderReturnsAllDataIfCollectionSizeIsWrong()
    {
        $this->collection
            ->expects($this->once())
            ->method('getSelect')
            ->will($this->returnValue($this->select));

        $this->reader = new MagentoReader($this->collection);
        $statement = $this->getMock('\Zend_Db_Statement_Interface');
        $this->select
            ->expects($this->once())
            ->method('query')
            ->will($this->returnValue($statement));

        $data = array(
            array('one' => 1, 'two' => 2, 'three' => 3),
            array('one' => 11, 'two' => 22, 'three' => 33),
            array('one' => 111, 'two' => 222, 'three' => 333),
        );

        $statement->expects($this->at(0))
            ->method('fetch')
            ->will($this->returnValue($data[0]));

        $statement->expects($this->at(1))
            ->method('fetch')
            ->will($this->returnValue($data[1]));

        $statement->expects($this->at(2))
            ->method('fetch')
            ->will($this->returnValue($data[2]));

        $i = 1;
        foreach ($this->reader as $key => $row) {
            $this->assertEquals($i, $key);
            $this->assertEquals($data[$i - 1], $row);
            $i++;
        }
    }
}
