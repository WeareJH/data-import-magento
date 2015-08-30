<?php

namespace Jh\DataImportMagentoTest\Service;

use Jh\DataImportMagento\Service\AttributeService;

/**
 * Class AttributeServiceTest
 * @package Jh\DataImportMagentoTest\Service
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class AttributeServiceTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var /Mage_Eav_Model_Entity_Attribute
     */
    protected $attrModel;

    /**
     * @var \Mage_Eav_Model_Entity_Attribute_Source_Table
     */
    protected $attrSrcModel;

    /**
     * @var AttributeService
     */
    protected $attributeService;

    public function setUp()
    {
        $this->attrModel        = $this->getMock('\Mage_Eav_Model_Entity_Attribute');
        $this->attrSrcModel     = $this->getMock('\Mage_Eav_Model_Entity_Attribute_Source_Table');
        $this->attributeService = new AttributeService($this->attrModel, $this->attrSrcModel);
    }

    public function testGetAttributeCreatesAttributeOptionIfItDoesNotExist()
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
            ->expects($this->exactly(2))
            ->method('setAttribute')
            ->with($attribute);

        $this->attrSrcModel
            ->expects($this->once())
            ->method('getAllOptions')
            ->with(false)
            ->will($this->returnValue($options));

        $this->attrSrcModel
            ->expects($this->once())
            ->method('getOptionId')
            ->with('option3')
            ->will($this->returnValue('code3'));

        $data = array(
            'value' => array(
                'option' => array('option3', 'option3')
            )
        );

        $attribute
            ->expects($this->once())
            ->method('usesSource')
            ->will($this->returnValue(true));

        $attribute
            ->expects($this->once())
            ->method('setData')
            ->with('option', $data);

        $attribute
            ->expects($this->once())
            ->method('save');

        $ret = $this->attributeService->getAttrCodeCreateIfNotExist('catalog_product', 'code3', 'option3');
        $this->assertEquals($ret, 'code3');
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
            ->expects($this->once())
            ->method('usesSource')
            ->will($this->returnValue(true));

        $attribute
            ->expects($this->never())
            ->method('setData');

        $attribute
            ->expects($this->never())
            ->method('save');

        $ret = $this->attributeService->getAttrCodeCreateIfNotExist('catalog_product', 'code2', 'option2');
        $this->assertEquals($ret, 'code2');
    }

    public function testGetAttributeReturnsValueIfAttributeDoesNotUseSource()
    {
        $attribute = $this->getMock('\Mage_Eav_Model_Entity_Attribute_Abstract');

        $this->attrModel
            ->expects($this->once())
            ->method('getIdByCode')
            ->with('catalog_product', 'attribute_code')
            ->will($this->returnValue(1));

        $this->attrModel
            ->expects($this->once())
            ->method('load')
            ->with(1)
            ->will($this->returnValue($attribute));

        $attribute
            ->expects($this->once())
            ->method('usesSource')
            ->will($this->returnValue(false));

        $ret = $this->attributeService->getAttrCodeCreateIfNotExist('catalog_product', 'attribute_code', 'some_value');
        $this->assertEquals($ret, 'some_value');
    }

    public function testGetAttributeThrowsExceptionIfAttributeDoesNotExist()
    {
        $this->setExpectedException(
            'Jh\DataImportMagento\Exception\AttributeNotExistException',
            'Attribute with code: "not_here" does not exist'
        );

        $this->attrModel
            ->expects($this->once())
            ->method('getIdByCode')
            ->with('catalog_product', 'not_here')
            ->will($this->returnValue(false));

        $this->attributeService->getAttrCodeCreateIfNotExist('catalog_product', 'not_here', 'some_value');
    }
}
