<?php

namespace Jh\DataImportMagento\Exception;

use Ddeboer\DataImport\Exception\ExceptionInterface;
use Exception;

/**
 * Class MagentoSaveException
 * @package Jh\DataImportMagento\Exception
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class MagentoSaveException extends Exception implements ExceptionInterface
{
    /**
     * @param \Mage_Core_Exception $e
     */
    public function __construct(\Mage_Core_Exception $e)
    {
        parent::__construct($e->getMessage(), $e->getCode(), $e);
    }
}
