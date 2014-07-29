<?php
namespace Jh\DataImportMagento\Writer\Product;

use Ddeboer\DataImport\Writer\AbstractWriter;
use Jh\DataImportMagento\Exception\MagentoSaveException;

/**
 * Class ProductUpdateAttributeWriter
 * @author Adam Paterson <hello@adampaterson.co.uk>
 * @package Jh\DataImportMagento\Writer\Product
 */
class ProductUpdateAttributeWriter extends AbstractWriter
{
    /**
     * @var \Mage_Catalog_Model_Product
     */
    protected $productModel;

    /**
     * @param \Mage_Catalog_Model_Product $productModel
     */
    public function __construct(\Mage_Catalog_Model_Product $productModel)
    {
        $this->productModel = $productModel;
    }

    /**
     * Write item if product exists
     *
     * @param array $item
     * @throws \Jh\DataImportMagento\Exception\MagentoSaveException
     */
    public function writeItem(array $item)
    {
        $productModel = clone $this->productModel;
        $product = $productModel->loadByAttribute('sku', $item['sku']);
        if (!$product) {
            return;
        }

        $product->addData($item);

        try {
            $product->save();
        } catch (\Mage_Core_Exception $e) {
            $message = $e->getMessage();
            throw new MagentoSaveException($message);
        }
    }
}
