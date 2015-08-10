<?php
/**
 * @author: Diego Cabrejas <diego@wearejh.com>
 */

namespace Jh\DataImportMagento\Service;

use Jh\DataImportMagento\Exception\AttributeNotExistException;

class AttributeService
{
    /**
     * @var \Mage_Eav_Model_Entity_Attribute
     */
    protected $eavAttrModel;

    /**
     * @var \Mage_Eav_Model_Entity_Attribute_Source_Table
     */
    protected $eavAttrSrcModel;

    /**
     * @param \Mage_Eav_Model_Entity_Attribute $eavAttrModel
     * @param \Mage_Eav_Model_Entity_Attribute_Source_Table $eavAttrSrcModel
     */
    public function __construct(
        \Mage_Eav_Model_Entity_Attribute $eavAttrModel,
        \Mage_Eav_Model_Entity_Attribute_Source_Table $eavAttrSrcModel
    ) {
        $this->eavAttrModel      = $eavAttrModel;
        $this->eavAttrSrcModel   = $eavAttrSrcModel;
    }

    /**
     * @param string $entityType
     * @param $attrCode
     * @param $attrValue
     * @return null
     * @throws AttributeNotExistException
     */
    public function getAttrCodeCreateIfNotExist($entityType , $attrCode, $attrValue)
    {
        $attrModel              = clone $this->eavAttrModel;
        $attributeOptionsModel  = clone $this->eavAttrSrcModel;

        $attributeId            = $attrModel->getIdByCode($entityType, $attrCode);

        if (false === $attributeId) {
            throw new AttributeNotExistException($attrCode);
        }

        $attribute = $attrModel->load($attributeId);

        if (!$attribute->usesSource()) {
            return $attrValue;
        }

        $attributeOptionsModel->setAttribute($attribute);
        $options = $attributeOptionsModel->getAllOptions(false);

        foreach ($options as $option) {
            if (strtolower($option['label']) == strtolower($attrValue)) {
                return $option['value'];
            }
        }

        //not found - create it
        $attribute->setData('option', array(
            'value' => array(
                'option' => array($attrValue, $attrValue)
            )
        ));
        $attribute->save();

        $attributeOptionsModel  = clone $this->eavAttrSrcModel;
        $attributeOptionsModel->setAttribute($attribute);
        $id = $attributeOptionsModel->getOptionId(strtolower($attrValue));

        return $id;
    }
}
