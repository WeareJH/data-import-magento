<?php

namespace Jh\DataImportMagentoTest\Service;

use Jh\DataImportMagento\Service\RemoteImageImporter;

/**
 * Class RemoteImageImporterTest
 * @package Jh\DataImportMagentoTest\Service
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class RemoteImageImporterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RemoteImageImporter
     */
    private $importer;

    /**
     * @var \Mage_Catalog_Model_Product
     */
    private $product;

    public function setup()
    {
        $this->importer = new RemoteImageImporter;
        $this->product = $this->getMock('\Mage_Catalog_Model_Product', [], [], '', false);
    }

    public function testImportImage()
    {
        $url  = __DIR__ . '/../Fixtures/honey.jpg';
        $path = realpath(__DIR__ . '/../../../');
        $path .= '/vendor/magento/magento/media/import/efba9ed5cc7df0bb6fc031bde060ffd4.jpg';

        $this->product
            ->expects($this->once())
            ->method('addImageToMediaGallery')
            ->with($path, ['thumbnail', 'small_image', 'image'], true, false);

        $resource = $this->getMockBuilder('Mage_Core_Model_Mysql4_Abstract')
            ->disableOriginalConstructor()
            ->getMock();

        $this->product
            ->expects($this->once())
            ->method('getResource')
            ->will($this->returnValue($resource));

        $resource
            ->expects($this->once())
            ->method('save')
            ->with($this->product);

        $this->importer->importImage($this->product, $url);

        unlink($path);
        rmdir(dirname($path));
    }
}
