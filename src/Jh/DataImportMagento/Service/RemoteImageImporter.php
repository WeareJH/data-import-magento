<?php

namespace Jh\DataImportMagento\Service;

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
     */
    public function importImage(\Mage_Catalog_Model_Product $product, $url)
    {
        $extension = pathinfo($url, PATHINFO_EXTENSION);
        $fileName  = sprintf('%s.%s', md5(sprintf('%s-%s', $url, $product->getSku())), $extension);
        $filePath  = sprintf('%s/import/%s', \Mage::getBaseDir('media'), $fileName);

        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        file_put_contents($filePath, file_get_contents($url));

        $mediaAttribute = [
            'thumbnail',
            'small_image',
            'image'
        ];

        $product->addImageToMediaGallery($filePath, $mediaAttribute, $move = true, $disable = false);
        $product->getResource()->save($product);
    }
}