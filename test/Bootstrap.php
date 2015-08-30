<?php

$files = [__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../../../autoload.php'];

foreach ($files as $file) {
    if (file_exists($file)) {
        $loader = require $file;

        break;
    }
}

$loader->add('Jh\\DataImportMagentoTest', __DIR__);
$loader->add('', __DIR__ . "/../vendor/wearejh/magento-ce/app");
$loader->add('', __DIR__ . "/../vendor/wearejh/magento-ce/app/code/community");
$loader->add('', __DIR__ . "/../vendor/wearejh/magento-ce/app/code/core");
$loader->add('', __DIR__ . "/../vendor/wearejh/magento-ce/lib");
$loader->add('Mage', __DIR__ . "/../vendor/wearejh/magento-ce/app/Mage.php");

$loader->register();

$kernel = \AspectMock\Kernel::getInstance();
$kernel->init([
    'debug' => true,
    'vendor' => __DIR__ . '/../vendor/',
    'includePaths' => [__DIR__ . '/../vendor/wearejh/magento-ce', __DIR__ . '/src'],
    'excludePaths' => [__DIR__]
]);

//bootstrap Magento - eugrh
\Mage::app('admin');
