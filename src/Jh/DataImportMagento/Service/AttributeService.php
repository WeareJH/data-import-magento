<?php

namespace Jh\DataImportMagento\Service;

use Jh\DataImportMagento\Exception\AttributeNotExistException;

/**
 * Class AttributeService
 * @package Jh\DataImportMagento\Service
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
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
     * @var array
     */
    protected $cachedAttributeOptionsValues = array();

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
     * @param string $attrCode
     * @param string $attrValue
     * @return string Attribute Value or ID
     * @throws AttributeNotExistException
     */
    public function getAttrCodeCreateIfNotExist($entityType, $attrCode, $attrValue)
    {
        $attrValue = strtolower($attrValue);
        if (isset($this->cachedAttributeOptionsValues[$entityType][$attrCode][$attrValue])) {
            return $this->cachedAttributeOptionsValues[$entityType][$attrCode][$attrValue];
        }

        $attrModel              = clone $this->eavAttrModel;
        $attributeOptionsModel  = clone $this->eavAttrSrcModel;
        $attributeId            = $attrModel->getIdByCode($entityType, $attrCode);

        if (false === $attributeId) {
            throw new AttributeNotExistException($attrCode);
        }

        $attribute = $attrModel->load($attributeId);

        if (!$attribute->usesSource()) {
            $this->cachedAttributeOptionsValues[$entityType][$attrCode][$attrValue] = $attrValue;
            return $attrValue;
        }

        $attributeOptionsModel->setAttribute($attribute);
        $options = $attributeOptionsModel->getAllOptions(false);

        foreach ($options as $option) {
            $optionId       = strtolower($option['value']);
            $optionValue    = strtolower($option['label']);

            $this->cachedAttributeOptionsValues[$entityType][$attrCode][$optionValue] = $optionId;
        }

        if (isset($this->cachedAttributeOptionsValues[$entityType][$attrCode][$attrValue])) {
            return $this->cachedAttributeOptionsValues[$entityType][$attrCode][$attrValue];
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
        $id = $attributeOptionsModel->getOptionId($attrValue);

        $this->cachedAttributeOptionsValues[$entityType][$attrCode][$attrValue] = $id;
        return $id;
    }
}
