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
     * @var \Mage_CatalogInventory_Model_Stock_Item
     */
    protected $stockModel;

    /**
     * @var array
     */
    protected $options = [
        'productIdField'    => 'sku',
        'stockUpdateType'   => self::STOCK_UPDATE_TYPE_SET,
    ];

    /**
     * @param \Mage_CatalogInventory_Model_Stock_Item $stockModel
     * @param \Mage_Catalog_Model_Product $productModel
     * @param array $options
     */
    public function __construct(
        \Mage_CatalogInventory_Model_Stock_Item $stockModel,
        \Mage_Catalog_Model_Product $productModel,
        array $options = array()
    ) {
        $this->stockModel   = $stockModel;
        $this->productModel = $productModel;
        $this->setOptions($options);
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
        //var_dump($this->options);
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

        $stockItem = clone $this->stockModel;

        //If Given a sku as the Product ID field, we need to get the product ID
        //from the actual product
        //TODO: We should be able to provide an option which allows setting the ID field for the actual product
        //TODO: So we could specify, that the id field is a product field, so load gthe product first
        //TODO: USing the given ID field and pass the Product ID to the stock model and load
        //TODO: ATM, if the ID field isn't 'sku' then it will attempt to load the stock model, using the ID
        //TODO field and ID value
        if ($this->options['productIdField'] === 'sku') {
            $product    = clone $this->productModel;
            $productId  = $product->getIdBySku($id);

            if (!$productId) {
                throw new WriterException(
                    sprintf('Product not found with SKU: "%s"', $id)
                );
            }

            $stockItem->load($productId, 'product_id');
            if (!$stockItem->getId()) {
                //TODO: Create it if it doesn't exist?

                throw new WriterException(
                    sprintf('No Stock Model found for Product with SKU: "%s"', $id)
                );
            }

        } else {
            $stockItem->load($id, $this->options['productIdField']);

            if (!$stockItem->getId()) {
                //TODO: Create it if it doesn't exist?

                throw new WriterException(
                    sprintf(
                        'No Stock Model found for ID: "%s" Using ID Field: "%s"',
                        $id,
                        $this->options['productIdField']
                    )
                );
            }
        }

        switch ($this->options['stockUpdateType']) {
            case self::STOCK_UPDATE_TYPE_ADD:
                $stockItem->setData('qty', $stockItem->getData('qty') + $item['qty']);
                break;
            case self::STOCK_UPDATE_TYPE_SET:
                $stockItem->setData('qty', $item['qty']);
                break;
        }

        try {
            $stockItem->save();
        } catch (\Mage_Core_Exception $e) {
            throw new MagentoSaveException($e);
        }

        return $this;
    }
}
