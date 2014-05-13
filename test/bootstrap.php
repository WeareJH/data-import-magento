<?php

$loader = require_once __DIR__.'/../vendor/autoload.php';
$loader->add('Jh\\DataImportMagentoTest', __DIR__);
$loader->add('', __DIR__ . "/../vendor/magento/magento/app/code/local");
$loader->add('', __DIR__ . "/../vendor/magento/magento/app/code/community");
$loader->add('', __DIR__ . "/../vendor/magento/magento/app/code/core");
$loader->add('', __DIR__ . "/../vendor/magento/magento/lib");

