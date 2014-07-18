<?php

$files = [__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../../../autoload.php'];

foreach ($files as $file) {
    if (file_exists($file)) {
        $loader = require $file;

        break;
    }
}

$loader->add('Jh\\DataImportMagentoTest', __DIR__);
$loader->add('', __DIR__ . "/../vendor/magento/magento/app");
$loader->add('', __DIR__ . "/../vendor/magento/magento/app/code/community");
$loader->add('', __DIR__ . "/../vendor/magento/magento/app/code/core");
$loader->add('', __DIR__ . "/../vendor/magento/magento/lib");
$loader->add('Mage', __DIR__ . "/../vendor/magento/magento/app/Mage.php");

//bootstrap Magento - eugrh
\Mage::app('admin');
