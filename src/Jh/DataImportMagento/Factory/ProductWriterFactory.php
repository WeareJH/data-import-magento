<?php

namespace Jh\DataImportMagento\Factory;

use Jh\DataImportMagento\Service\AttributeService;
use Jh\DataImportMagento\Service\ConfigurableProductService;
use Jh\DataImportMagento\Service\RemoteImageImporter;
use Jh\DataImportMagento\Writer\ProductWriter;
use Psr\Log\LoggerInterface;

/**
 * Class ProductWriterFactory
 * @package Jh\DataImportMagento\Factory
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class ProductWriterFactory
{
    /**
     * @return ProductWriter
     */
    public function __invoke(LoggerInterface $logger)
    {
        $productModel           = \Mage::getModel('catalog/product');
        $eavAttrModel           = \Mage::getModel('eav/entity_attribute');
        $eavAttrSrcModel        = \Mage::getModel('eav/entity_attribute_source_table');

        return new ProductWriter(
            $productModel,
            new RemoteImageImporter,
            new AttributeService($eavAttrModel, $eavAttrSrcModel),
            new ConfigurableProductService($eavAttrModel, \Mage::getModel('catalog/product')),
            $logger
        );
    }
}
