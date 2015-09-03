<?php

namespace Jh\DataImportMagento\Service;

use Mage_Core_Exception;
use RuntimeException;

/**
 * Class RemoteImageImporter
 * @package Jh\DataImportMagento\Service
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class RemoteImageImporter
{

    /**
     * @param \Mage_Catalog_Model_Product $product
     * @param string                      $url
     * @throws RuntimeException
     */
    public function importImage(\Mage_Catalog_Model_Product $product, $url)
    {
        $url       = trim($url);
        $extension = pathinfo($url, PATHINFO_EXTENSION);
        $fileName  = sprintf('%s.%s', md5(sprintf('%s-%s', basename($url), $product->getSku())), $extension);
        $filePath  = sprintf('%s/import/%s', \Mage::getBaseDir('media'), $fileName);

        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        $data = @file_get_contents($url);
        if ($data == false) {
            throw new RuntimeException(sprintf('URL returned nothing: "%s"', $url));
        }

        file_put_contents($filePath, $data);

        $mediaAttribute = [
            'thumbnail',
            'small_image',
            'image'
        ];

        try {
            $product->addImageToMediaGallery($filePath, $mediaAttribute, $move = true, $disable = false);
            $product->getResource()->save($product);
        } catch (Mage_Core_Exception $e) {
            throw new RuntimeException($e->getMessage());
        }
    }
}
