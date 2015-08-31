<?php

namespace Jh\DataImportMagento\ValueConverter;

use Ddeboer\DataImport\Exception\UnexpectedTypeException;
use Ddeboer\DataImport\Exception\UnexpectedValueException;
use Ddeboer\DataImport\ValueConverter\ValueConverterInterface;

/**
 * Class ProductStatusValueConverter
 * @package Jh\DataImportMagento\ValueConverter
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ProductStatusValueConverter implements ValueConverterInterface
{

    /**
     * @var array
     */
    private $productStatuses = [];

    /**
     * @var string
     */
    private $default = 'Disabled';

    /**
     *  Get the Tax Classes
     */
    public function __construct()
    {
        $this->productStatuses = \Mage_Catalog_Model_Product_Status::getOptionArray();
    }

    /**
     * @param string $input
     * @return string
     */
    public function convert($input)
    {
        if (empty($input)) {
            $input = $this->default;
        }

        if (!in_array($input, $this->productStatuses)) {
            throw new UnexpectedValueException(
                sprintf(
                    'Given Product Status: "%s" is not valid. Allowed values: "%s"',
                    $input,
                    implode('", "', $this->productStatuses)
                )
            );
        }

        return array_search($input, $this->productStatuses);
    }
}
