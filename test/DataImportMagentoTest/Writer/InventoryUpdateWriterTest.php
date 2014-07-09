<?php

namespace Jh\DataImportMagentoTest\Writer;

use Jh\DataImportMagento\Writer\InventoryUpdateWriter;

/**
 * Class InventoryUpdateWriterTest
 * @package Jh\DataImportMagentoTest\Writer
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class InventoryUpdateWriterTest extends \PHPUnit_Framework_TestCase
{
    protected $inventoryUpdateWriter;

    protected $stockItemModel;

    protected $productModel;

    protected $options = array();

    public function setUp()
    {
        $this->stockItemModel   = $this->getMock('\Mage_CatalogInventory_Model_Stock_Item');
        $this->productModel     = $this->getMockBuilder('\Mage_Catalog_Model_Product')
            ->disableOriginalConstructor()
            ->getMock();

        $this->inventoryUpdateWriter = new InventoryUpdateWriter(
            $this->stockItemModel,
            $this->productModel
        );
    }

    public function getInventoryWriter()
    {
        return new InventoryUpdateWriter(
            $this->stockItemModel,
            $this->productModel,
            $this->options
        );
    }


    public function testCantSetUnrecognizedUpdateType()
    {
        $this->options['stockUpdateType'] = 'notatype';
        $this->setExpectedException(
            '\InvalidArgumentException',
            "'notatype' is not a valid value for 'stockUpdateType'"
        );

        $writer = $this->getInventoryWriter();
    }

    public function testCanSetValidUpdateType()
    {
        $this->options['stockUpdateType'] = 'add';
        $writer = $this->getInventoryWriter();
        $this->assertInstanceOf('Jh\DataImportMagento\Writer\InventoryUpdateWriter', $writer);

        $this->options['stockUpdateType'] = 'set';
        $writer = $this->getInventoryWriter();
        $this->assertInstanceOf('Jh\DataImportMagento\Writer\InventoryUpdateWriter', $writer);
    }

    /**
     * @dataProvider necessaryFieldsProvider
     */
    public function testExceptionIsThrownIfNecessaryFieldsNotFoundInData($field, $data, $message)
    {
        $writer = $this->getInventoryWriter();

        $this->setExpectedException('Ddeboer\DataImport\Exception\WriterException', $message);
        $writer->writeItem($data);
    }

    public function necessaryFieldsProvider()
    {
        return [
            ['product_id',  [],                     'No product Id Found'],
            ['qty',         ['product_id' => 2],    'No Quantity found for Product: "2". Using field "qty"'],
        ];
    }

    public function testExceptionIsThrownIfProductCannotBeLoadedBySku()
    {
        $sku = 'PROD1234';
        $data = ['product_id' => $sku, 'qty' => 10];

        $this->productModel
            ->expects($this->once())
            ->method('getIdBySku')
            ->with($sku)
            ->will($this->returnValue(null));

        $writer = $this->getInventoryWriter();

        $message = 'Product not found with SKU: "PROD1234"';
        $this->setExpectedException('Ddeboer\DataImport\Exception\WriterException', $message);
        $writer->writeItem($data);
    }

    public function testExceptionIsThrownIfStockModelCannotBeLoadedByProductId()
    {
        $productId = 2;
        $sku = 'PROD1234';
        $data = ['product_id' => $sku, 'qty' => 10];

        $this->productModel
            ->expects($this->once())
            ->method('getIdBySku')
            ->with($sku)
            ->will($this->returnValue($productId));

        $this->stockItemModel
            ->expects($this->once())
            ->method('load')
            ->with($productId, 'product_id');

        $this->stockItemModel
            ->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(null));

        $writer = $this->getInventoryWriter();

        $message = 'No Stock Model found for Product with SKU: "PROD1234"';
        $this->setExpectedException('Ddeboer\DataImport\Exception\WriterException', $message);
        $writer->writeItem($data);
    }

    public function testExceptionIsThrownIfProductCannotBeLoadedByCustomField()
    {
        $id = 5;
        $data = ['product_id' => $id, 'qty' => 10];

        $this->options['productIdField'] = 'item_id';

        $this->productModel
            ->expects($this->never())
            ->method('getIdBySku');

        $this->stockItemModel
            ->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(null));

        $writer = $this->getInventoryWriter();

        $message = 'No Stock Model found for ID: "5" Using ID Field: "item_id"';
        $this->setExpectedException('Ddeboer\DataImport\Exception\WriterException', $message);
        $writer->writeItem($data);
    }

    public function testStockQtyIsSetWhenUpdateModeIsSet()
    {
        $id = 5;
        $data = ['product_id' => $id, 'qty' => 10];

        $this->options['productIdField'] = 'item_id';

        $this->productModel
            ->expects($this->never())
            ->method('getIdBySku');

        $this->stockItemModel
            ->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));

        $this->stockItemModel
            ->expects($this->once())
            ->method('setData')
            ->with('qty', 10);

        $this->stockItemModel
            ->expects($this->once())
            ->method('save');

        $writer = $this->getInventoryWriter();
        $writer->writeItem($data);
    }

    public function testStockQtyIsAddedToWhenUpdateModeIsAdd()
    {
        $this->options['stockUpdateType'] = 'add';
        $id = 5;
        $data = ['product_id' => $id, 'qty' => 10];

        $this->options['productIdField'] = 'item_id';

        $this->productModel
            ->expects($this->never())
            ->method('getIdBySku');

        $this->stockItemModel
            ->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));

        $this->stockItemModel
            ->expects($this->once())
            ->method('getData')
            ->with('qty')
            ->will($this->returnValue(5));

        $this->stockItemModel
            ->expects($this->once())
            ->method('setData')
            ->with('qty', 15);

        $this->stockItemModel
            ->expects($this->once())
            ->method('save');

        $writer = $this->getInventoryWriter();
        $writer->writeItem($data);
    }

    public function testMagentoSaveExceptionIsThrownIfSaveFails()
    {
        $productId = 2;
        $sku = 'PROD1234';
        $data = ['product_id' => $sku, 'qty' => 10];

        $this->productModel
            ->expects($this->once())
            ->method('getIdBySku')
            ->with($sku)
            ->will($this->returnValue($productId));

        $this->stockItemModel
            ->expects($this->once())
            ->method('load')
            ->with($productId, 'product_id');

        $this->stockItemModel
            ->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(4));

        $e = new \Mage_Customer_Exception("Save Failed");
        $this->stockItemModel
            ->expects($this->once())
            ->method('save')
            ->will($this->throwException($e));

        $this->setExpectedException('Jh\DataImportMagento\Exception\MagentoSaveException', 'Save Failed');
        $writer = $this->getInventoryWriter();
        $writer->writeItem($data);
    }

    public function testPrepareReturnsSelf()
    {
        $writer = $this->getInventoryWriter();
        $this->assertSame($writer, $writer->prepare());
    }
}
