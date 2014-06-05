<?php

namespace Jh\DataImportMagentoTest\Writer;

use Jh\DataImportMagento\Writer\ProductWriter;

/**
 * Class ProductWriterTest
 * @package Jh\DataImportMagentoTest\Writer
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ProductWriterTest extends \PHPUnit_Framework_TestCase
{

    protected $productWriter;
    protected $productModel;
    protected $attrModel;
    protected $attrSrcModel;

    public function setUp()
    {
        $this->productModel     = $this->getMock('\Mage_Catalog_Model_Product', array(), array(), '', false);
        $this->attrModel        = $this->getMock('\Mage_Eav_Model_Entity_Attribute');
        $this->attrSrcModel     = $this->getMock('\Mage_Eav_Model_Entity_Attribute_Source_Table');

        $this->productWriter    = new ProductWriter($this->productModel, $this->attrModel, $this->attrSrcModel);
    }


    public function testProcessAttributes()
    {
        $attributes = array(
            'code1' => 'option1',
            'code2' => 'option2',
        );

        $this->productModel
            ->expects($this->at(0))
            ->method('setData')
            ->with('code1', 'option1');

        $this->productModel
            ->expects($this->at(1))
            ->method('setData')
            ->with('code2', 'option2');

        $this->productWriter = $this->getMockBuilder('\Jh\DataImportMagento\Writer\ProductWriter')
            ->setMethods(array('getAttrCodeCreateIfNotExist'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->productWriter
            ->expects($this->at(0))
            ->method('getAttrCodeCreateIfNotExist')
            ->with('code1', 'option1')
            ->will($this->returnValue('option1'));

        $this->productWriter
            ->expects($this->at(1))
            ->method('getAttrCodeCreateIfNotExist')
            ->with('code2', 'option2')
            ->will($this->returnValue('option2'));

        $this->productWriter->processAttributes($attributes, $this->productModel);
    }


    public function testProcessAttributesSkipsNullValues()
    {
        $this->productModel
            ->expects($this->never())
            ->method('setData');

        $this->productWriter->processAttributes(array('code1' => null), $this->productModel);
    }

    public function testGetAttributeCreatesAttributeIfItDoesNotExist()
    {

        $attribute = $this->getMock('\Mage_Eav_Model_Entity_Attribute_Abstract');

        $options = array(
            array('label' => 'option1', 'value' => 'code1'),
            array('label' => 'option2', 'value' => 'code2'),
        );

        $this->attrModel
             ->expects($this->once())
             ->method('getIdByCode')
             ->with('catalog_product', 'code3')
             ->will($this->returnValue(1));

        $this->attrModel
             ->expects($this->once())
             ->method('load')
             ->with(1)
             ->will($this->returnValue($attribute));

        $this->attrSrcModel
             ->expects($this->once())
             ->method('setAttribute')
             ->with($attribute);

        $this->attrSrcModel
            ->expects($this->once())
            ->method('getAllOptions')
            ->with(false)
            ->will($this->returnValue($options));

        $data = array(
            'value' => array(
                'option' => array('option3', 'option3')
            )
        );

        $attribute
            ->expects($this->once())
            ->method('setData')
            ->with('option', $data);

        $attribute
            ->expects($this->once())
            ->method('save');

        $ret = $this->productWriter->getAttrCodeCreateIfNotExist('code3', 'option3');
        $this->assertEquals($ret, 'option3');
    }

    public function testGetAttributeReturnsIdIfItExists()
    {
        $attribute = $this->getMock('\Mage_Eav_Model_Entity_Attribute_Abstract');

        $options = array(
            array('label' => 'option1', 'value' => 'code1'),
            array('label' => 'option2', 'value' => 'code2'),
        );

        $this->attrModel
            ->expects($this->once())
            ->method('getIdByCode')
            ->with('catalog_product', 'code2')
            ->will($this->returnValue(1));

        $this->attrModel
            ->expects($this->once())
            ->method('load')
            ->with(1)
            ->will($this->returnValue($attribute));

        $this->attrSrcModel
            ->expects($this->once())
            ->method('setAttribute')
            ->with($attribute);

        $this->attrSrcModel
            ->expects($this->once())
            ->method('getAllOptions')
            ->with(false)
            ->will($this->returnValue($options));

        $attribute
            ->expects($this->never())
            ->method('setData');

        $attribute
            ->expects($this->never())
            ->method('save');

        $ret = $this->productWriter->getAttrCodeCreateIfNotExist('code2', 'option2');
        $this->assertEquals($ret, 'code2');
    }

    public function testPrepareMethodSetsUpDataCorrectly()
    {
        $this->productModel
            ->expects($this->once())
            ->method('getDefaultAttributeSetId')
            ->will($this->returnValue(1));

        $this->productWriter->prepare();
    }

    public function testWriteItemSuccessfullySaves()
    {
        $data = array(
            'name'              => 'Product 1',
            'description'       => 'Description',
            'attributes'        => array(),
            'attribute_set_id'  => 0,
            'stock_data'        => array(),
            'weight'            => 0,
        );

        $this->productWriter = $this->getMockBuilder('\Jh\DataImportMagento\Writer\ProductWriter')
            ->setMethods(array('processAttributes'))
            ->setConstructorArgs(array($this->productModel, $this->attrModel, $this->attrSrcModel))
            ->getMock();

        $refObject   = new \ReflectionObject($this->productWriter);
        $refProperty = $refObject->getProperty('defaultProductData');
        $refProperty->setAccessible(true);
        $refProperty->setValue($this->productWriter, array());

        $this->productWriter
             ->expects($this->once())
             ->method('processAttributes')
             ->with($data['attributes'], $this->productModel);

        $this->productModel
            ->expects($this->once())
            ->method('setData')
            ->with($data);

        $this->productModel
            ->expects($this->once())
            ->method('save')
            ->with($this->productModel);

        $this->productWriter->writeItem($data);
    }

    public function testMagentoSaveExceptionIsThrownIfSaveFails()
    {
        $data = array(
            'name'              => 'Product 1',
            'description'       => 'Description',
            'attribute_set_id'  => 0,
            'stock_data'        => array(),
            'weight'            => 0,
        );

        $refObject   = new \ReflectionObject($this->productWriter);
        $refProperty = $refObject->getProperty('defaultProductData');
        $refProperty->setAccessible(true);
        $refProperty->setValue($this->productWriter, array());

        $this->productModel
            ->expects($this->once())
            ->method('setData')
            ->with($data);

        $e = new \Mage_Customer_Exception("Save Failed");
        $this->productModel
            ->expects($this->once())
            ->method('save')
            ->with($this->productModel)
            ->will($this->throwException($e));

        $this->setExpectedException('Jh\DataImportMagento\Exception\MagentoSaveException', 'Save Failed');
        $this->productWriter->writeItem($data);
    }

    public function testDefaultsAreUsedForProductIfNotExistInInputData()
    {
        $data = array(
            'name'              => 'Product 1',
            'description'       => 'Description',
        );

        $refObject   = new \ReflectionObject($this->productWriter);
        $refProperty = $refObject->getProperty('defaultStockData');
        $refProperty->setAccessible(true);
        $refProperty->setValue($this->productWriter, array('someKey' => 'someValue'));

        $refProperty = $refObject->getProperty('defaultProductData');
        $refProperty->setAccessible(true);
        $refProperty->setValue($this->productWriter, array('someKey' => 'someValue'));

        $expected = array(
            'name'              => 'Product 1',
            'description'       => 'Description',
            'attribute_set_id'  => 0,
            'stock_data'        => array(
                'someKey' => 'someValue'
            ),
            'weight'            => 0,
            'someKey'           => 'someValue'
        );

        $this->productModel
            ->expects($this->once())
            ->method('setData')
            ->with($expected);

        $e = new \Mage_Customer_Exception("Save Failed");
        $this->productModel
            ->expects($this->once())
            ->method('save')
            ->with($this->productModel)
            ->will($this->throwException($e));

        $this->setExpectedException('Jh\DataImportMagento\Exception\MagentoSaveException', 'Save Failed');
        $this->productWriter->writeItem($data);
    }
}
