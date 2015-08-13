<?php

namespace Jh\DataImportMagento\Writer;

use Ddeboer\DataImport\Exception\WriterException;
use Ddeboer\DataImport\Writer\AbstractWriter;
use Guzzle\Common\Exception\InvalidArgumentException;
use Jh\DataImportMagento\Exception\MagentoSaveException;
use Jh\DataImportMagento\Options\OptionsParseTrait;

/**
 * Class InventoryUpdateWriter
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 * @package Jh\DataImportMagento\Writer
 */
class InventoryUpdateWriter extends AbstractWriter
{
    use OptionsParseTrait;

    /**
     * Whether to add qty to existing qty
     */
    const STOCK_UPDATE_TYPE_ADD = 'add';

    /**
     * Whether to set qty and override current
     */
    const STOCK_UPDATE_TYPE_SET = 'set';

    /**
     * @var array
     */
    protected $options = [
        'productIdField'    => 'sku',
        'stockUpdateType'   => self::STOCK_UPDATE_TYPE_SET,
        'updateStockStatusIfInStock'    => true
    ];

    /**
     * @param \Mage_Catalog_Model_Product $productModel
     * @param array $options
     */
    public function __construct(
        \Mage_Catalog_Model_Product $productModel,
        array $options = array()
    ) {
        $this->productModel = $productModel;
        if (!empty($options)) {
            $this->setOptions($options);
        }
    }

    /**
     * @param $options
     */
    public function setOptions(array $options)
    {
        if (isset($options['stockUpdateType'])) {
            //check the type passed in, is actually type we support
            if ($options['stockUpdateType'] !== self::STOCK_UPDATE_TYPE_SET
                && $options['stockUpdateType'] !== self::STOCK_UPDATE_TYPE_ADD
            ) {
                throw new \InvalidArgumentException(
                    sprintf("'%s' is not a valid value for 'stockUpdateType'", $options['stockUpdateType'])
                );
            }
        }
        $this->options = $this->parseOptions($this->options, $options);
    }

    /**
     * @return \Ddeboer\DataImport\Writer\WriterInterface
     */
    public function prepare()
    {
        return $this;
    }

    /**
     * @param array $item
     * @return \Ddeboer\DataImport\Writer\WriterInterface
     * @throws \Ddeboer\DataImport\Exception\WriterException
     * @throws \Jh\DataImportMagento\Exception\MagentoSaveException
     */
    public function writeItem(array $item)
    {
        if (!isset($item['product_id'])) {
            throw new WriterException("No product Id Found");
        }

        $id = $item['product_id'];

        if (!isset($item['qty'])) {
            throw new WriterException(
                sprintf('No Quantity found for Product: "%s". Using field "qty"', $id)
            );
        }

        //If Given a sku as the Product ID field, we need to get the product ID
        //from the actual product
        $product = clone $this->productModel;
        if ($this->options['productIdField'] === 'sku') {
            $id = $product->getIdBySku($id);
            if (!$id) {
                throw new WriterException(
                    sprintf('Product not found with SKU: "%s"', $id)
                );
            }
        }

        $product->load($id);
        $stockItem = $product->getStockItem();

        switch ($this->options['stockUpdateType']) {
            case self::STOCK_UPDATE_TYPE_ADD:
                $stockItem->setData('qty', $stockItem->getData('qty') + $item['qty']);
                break;
            case self::STOCK_UPDATE_TYPE_SET:
                $stockItem->setData('qty', $item['qty']);
                break;
        }

        if ($this->options['updateStockStatusIfInStock']) {
            // set item to in stock if the new qty matches or is greater than min qty in the config
            if ($item['qty'] >= $stockItem->getMinQty()) {
                $stockItem->setData('is_in_stock', 1);
            }
        }

        try {
            $stockItem->save();
        } catch (\Mage_Core_Exception $e) {
            throw new MagentoSaveException($e);
        }

        return $this;
    }
}
