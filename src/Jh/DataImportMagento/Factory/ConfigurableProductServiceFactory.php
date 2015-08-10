<?php

namespace Jh\DataImportMagento\Factory;

use Jh\DataImportMagento\Service\ConfigurableProductService;

/**
 * Class ConfigurableProductServiceFactory
 * @author: Diego Cabrejas <diego@wearejh.com>
 */
class ConfigurableProductServiceFactory
{

    /**
     * @param \Mage_Eav_Model_Entity_Attribute $eavAttrModel
     * @return ConfigurableProductService
     */
    public function makeConfigurableProductService(\Mage_Eav_Model_Entity_Attribute $eavAttrModel)
    {
        return new ConfigurableProductService($eavAttrModel);
    }
}
