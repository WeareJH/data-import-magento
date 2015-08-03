<?php

namespace Jh\DataImportMagento\Exception;

use Ddeboer\DataImport\Exception\ExceptionInterface;
use Exception;

/**
 * Class AttributeNotExistException
 * @package Jh\DataImportMagento\Exception
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class AttributeNotExistException extends Exception implements ExceptionInterface
{

    /**
     * @param string $attributeCode
     */
    public function __construct($attributeCode)
    {
        $message = sprintf('Attribute with code: "%s" does not exist', $attributeCode);
        parent::__construct($message);
    }
}
