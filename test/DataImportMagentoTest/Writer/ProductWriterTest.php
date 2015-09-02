<?php

namespace Jh\DataImportMagentoTest\Writer;

use Jh\DataImportMagento\Writer\ProductWriter;
use Psr\Log\LoggerInterface;

/**
 * Class ProductWriterTest
 * @package Jh\DataImportMagentoTest\Writer
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ProductWriterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductWriter
     */
    protected $productWriter;
    protected $productModel;
    protected $attributeService;
    protected $remoteImageImporter;
    protected $configurableProductService;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function setUp()
    {
        $this->productModel = $this->getMock('\Mage_Catalog_Model_Product', array(), array(), '', false);
        $this->remoteImageImporter = $this->getMock('\Jh\DataImportMagento\Service\RemoteImageImporter');
        $this->logger = $this->getMock('\Psr\Log\LoggerInterface');

        $this->attributeService = $this->getMockBuilder('Jh\DataImportMagento\Service\AttributeService')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configurableProductService =
            $this->getMockBuilder('\Jh\DataImportMagento\Service\ConfigurableProductService')
            ->disableOriginalConstructor()
            ->getMock();

        $this->productWriter = new ProductWriter(
            $this->productModel,
            $this->remoteImageImporter,
            $this->attributeService,
            $this->configurableProductService,
            $this->logger
        );
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
            'weight'            => '0',
            'status'            => '1',
            'tax_class_id'      => 2,
            'website_ids'       => [1],
            'type_id'           => 'simple',
            'url_key'           => null
        );

        $expected = $data;
        unset($expected['attributes']);
        $this->productModel
            ->expects($this->once())
            ->method('addData')
            ->with($expected);

        $this->productModel
            ->expects($this->once())
            ->method('save');

        $this->productWriter->writeItem($data);
    }

    public function testWriteWithAttributesDelegatesToAttributeService()
    {
        $data = array(
            'name'              => 'Product 1',
            'description'       => 'Description',
            'attributes'        => array(
                'code1' => 'option1',
                'code2' => 'option2',
            ),
            'attribute_set_id'  => 0,
            'stock_data'        => array(),
            'weight'            => '0',
            'status'            => '1',
            'tax_class_id'      => 2,
            'website_ids'       => [1],
            'type_id'           => 'simple',
            'url_key'           => null
        );

        $this->productModel
            ->expects($this->at(0))
            ->method('setData')
            ->with('code1', 'option1');

        $this->productModel
            ->expects($this->at(1))
            ->method('setData')
            ->with('code2', 'option2');

        $expected = $data;
        unset($expected['attributes']);
        $this->productModel
            ->expects($this->once())
            ->method('addData')
            ->with($expected);

        $this->attributeService
            ->expects($this->at(0))
            ->method('getAttrCodeCreateIfNotExist')
            ->with('catalog_product', 'code1', 'option1')
            ->will($this->returnValue('option1'));

        $this->attributeService
            ->expects($this->at(1))
            ->method('getAttrCodeCreateIfNotExist')
            ->with('catalog_product', 'code2', 'option2')
            ->will($this->returnValue('option2'));

        $this->productModel
            ->expects($this->once())
            ->method('save');

        $this->productWriter->writeItem($data);
    }


    public function testWriteItemWithNullAttributesAreSkipped()
    {
        $data = array(
            'name'              => 'Product 1',
            'description'       => 'Description',
            'attributes'        => array(
                'code1' => null,
            ),
            'attribute_set_id'  => 0,
            'stock_data'        => array(),
            'weight'            => '0',
            'status'            => '1',
            'tax_class_id'      => 2,
            'website_ids'       => [1],
            'type_id'           => 'simple',
            'url_key'           => null
        );

        $expected = $data;
        unset($expected['attributes']);
        $this->productModel
            ->expects($this->once())
            ->method('addData')
            ->with($expected);

        $this->attributeService
            ->expects($this->never())
            ->method('getAttrCodeCreateIfNotExist');

        $this->productModel
            ->expects($this->once())
            ->method('save');

        $this->productWriter->writeItem($data);
    }

    public function testCreateConfigurableProductThrowsExceptionIfNoAttributesSpecified()
    {
        $data = array(
            'name'                      => 'Product 1',
            'description'               => 'Description',
            'configurable_attributes'   => [],
            'attribute_set_id'          => 0,
            'stock_data'                => array(),
            'weight'                    => '0',
            'status'                    => '1',
            'tax_class_id'              => 2,
            'website_ids'               => [1],
            'type_id'                   => 'configurable',
            'url_key'                   => null,
            'sku'                       => 'PROD1'
        );

        $this->productModel
            ->expects($this->once())
            ->method('addData')
            ->with($data);

        $this->setExpectedException(
            '\Jh\DataImportMagento\Exception\MagentoSaveException',
            'Configurable product with SKU: "PROD1" must have at least one "configurable_attribute" defined'
        );

        $this->productWriter->writeItem($data);
    }

    public function testCreateConfigurableProductDelegatesToConfigService()
    {
        $data = array(
            'name'                      => 'Product 1',
            'description'               => 'Description',
            'configurable_attributes'   => ['Colour'],
            'attribute_set_id'          => 0,
            'stock_data'                => array(),
            'weight'                    => '0',
            'status'                    => '1',
            'tax_class_id'              => 2,
            'website_ids'               => [1],
            'type_id'                   => 'configurable',
            'url_key'                   => null,
            'sku'                       => 'PROD1'
        );

        $this->productModel
            ->expects($this->once())
            ->method('addData')
            ->with($data);

        $this->productModel
            ->expects($this->once())
            ->method('save');

        $this->configurableProductService
            ->expects($this->once())
            ->method('setupConfigurableProduct')
            ->with($this->productModel, ['Colour']);

        $this->productWriter->writeItem($data);
    }

    public function testSimpleProductWithParentIsConfigured()
    {
        $data = array(
            'name'                      => 'Product 1',
            'description'               => 'Description',
            'attribute_set_id'          => 0,
            'stock_data'                => array(),
            'weight'                    => '0',
            'status'                    => '1',
            'tax_class_id'              => 2,
            'website_ids'               => [1],
            'type_id'                   => 'simple',
            'url_key'                   => null,
            'sku'                       => 'PROD1',
            'parent_sku'                => 'PARENT1',
        );

        $this->productModel
            ->expects($this->once())
            ->method('addData')
            ->with($data);

        $this->productModel
            ->expects($this->once())
            ->method('save');

        $this->configurableProductService
            ->expects($this->once())
            ->method('assignSimpleProductToConfigurable')
            ->with($this->productModel, 'PARENT1');

        $this->productWriter->writeItem($data);
    }

    public function testImagesAreImported()
    {
        $data = array(
            'name'                      => 'Product 1',
            'description'               => 'Description',
            'attribute_set_id'          => 0,
            'stock_data'                => array(),
            'weight'                    => '0',
            'status'                    => '1',
            'tax_class_id'              => 2,
            'website_ids'               => [1],
            'type_id'                   => 'simple',
            'url_key'                   => null,
            'sku'                       => 'PROD1',
            'images'                    => [
                'http://image.com/image1.jpg',
                'http://image.com/image2.jpg',
            ]
        );

        $this->productModel
            ->expects($this->once())
            ->method('addData')
            ->with($data);

        $this->productModel
            ->expects($this->once())
            ->method('save');

        $this->remoteImageImporter
            ->expects($this->at(0))
            ->method('importImage')
            ->with($this->productModel, 'http://image.com/image1.jpg');

        $this->remoteImageImporter
            ->expects($this->at(1))
            ->method('importImage')
            ->with($this->productModel, 'http://image.com/image2.jpg');

        $this->productWriter->writeItem($data);
    }

    public function testErrorIsLoggedIfImageCouldNotBeImported()
    {
        $data = array(
            'name'                      => 'Product 1',
            'description'               => 'Description',
            'attribute_set_id'          => 0,
            'stock_data'                => array(),
            'weight'                    => '0',
            'status'                    => '1',
            'tax_class_id'              => 2,
            'website_ids'               => [1],
            'type_id'                   => 'simple',
            'url_key'                   => null,
            'sku'                       => 'PROD1',
            'images'                    => ['http://image.com/image1.jpg']
        );

        $this->productModel
            ->expects($this->once())
            ->method('addData')
            ->with($data);

        $this->productModel
            ->expects($this->once())
            ->method('save');

        $this->remoteImageImporter
            ->expects($this->once())
            ->method('importImage')
            ->with($this->productModel, 'http://image.com/image1.jpg')
            ->will($this->throwException(new \RuntimeException('nope!')));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Error importing image for product with SKU: "PROD1". Error: "nope!"');

        $this->productWriter->writeItem($data);
    }

    public function testMagentoSaveExceptionIsThrownIfSaveFails()
    {
        $data = array(
            'name'              => 'Product 1',
            'description'       => 'Description',
            'attribute_set_id'  => 0,
            'stock_data'        => array(),
            'weight'            => '0',
            'status'            => '1',
            'tax_class_id'      => 2,
            'website_ids'       => [1],
            'type_id'           => 'simple',
            'url_key'           => null
        );


        $this->productModel
            ->expects($this->once())
            ->method('addData')
            ->with($data);


        $e = new \Mage_Customer_Exception("Save Failed");
        $this->productModel
            ->expects($this->once())
            ->method('save')
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

        $expected = array(
            'name'              => 'Product 1',
            'description'       => 'Description',
            'attribute_set_id'  => null,
            'stock_data'        => [
                'manage_stock'                  => 1,
                'use_config_manage_stock'       => 1,
                'qty'                           => 0,
                'min_qty'                       => 0,
                'use_config_min_qty'            => 1,
                'min_sale_qty'                  => 1,
                'use_config_min_sale_qty'       => 1,
                'max_sale_qty'                  => 10000,
                'use_config_max_sale_qty'       => 1,
                'is_qty_decimal'                => 0,
                'backorders'                    => 0,
                'use_config_backorders'         => 1,
                'notify_stock_qty'              => 1,
                'use_config_notify_stock_qty'   => 1,
                'enable_qty_increments'         => 0,
                'use_config_enable_qty_inc'     => 1,
                'qty_increments'                => 0,
                'use_config_qty_increments'     => 1,
                'is_in_stock'                   => 0,
                'low_stock_date'                => null,
                'stock_status_changed_auto'     => 0
            ],
            'weight'            => '0',
            'status'            => '1',
            'tax_class_id'      => 2,
            'website_ids'       => [1],
            'type_id'           => 'simple',
            'url_key'           => null
        );

        $this->productModel
            ->expects($this->once())
            ->method('addData')
            ->with($expected);

        $e = new \Mage_Customer_Exception("Save Failed");
        $this->productModel
            ->expects($this->once())
            ->method('save')
            ->will($this->throwException($e));

        $this->setExpectedException('Jh\DataImportMagento\Exception\MagentoSaveException', 'Save Failed');
        $this->productWriter->prepare();
        $this->productWriter->writeItem($data);
    }
}
