<?php

namespace Jh\DataImportMagento\ValueConverter;

use Ddeboer\DataImport\Exception\UnexpectedTypeException;
use Ddeboer\DataImport\Exception\UnexpectedValueException;
use Ddeboer\DataImport\ValueConverter\ValueConverterInterface;

/**
 * Class TaxClassValueConverter
 * @package Jh\DataImportMagento\ValueConverter
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class TaxClassValueConverter implements ValueConverterInterface
{

    /**
     * @var array
     */
    private $taxClasses = [];

    /**
     * @var string
     */
    private $default = 'Taxable Goods';

    /**
     *  Get the Tax Classes
     */
    public function __construct()
    {
        $model = \Mage::getSingleton('tax/class_source_product');
        $productTaxClassOptions = $model->getAllOptions();
        foreach ($productTaxClassOptions as $option) {
            $this->taxClasses[$option['value']] = $option['label'];
        }
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

        if (!in_array($input, $this->taxClasses)) {
            throw new UnexpectedValueException(
                sprintf(
                    'Given Tax-Class: "%s" is not valid. Allowed values: "%s"',
                    $input,
                    implode('", "', $this->taxClasses)
                )
            );
        }

        return array_search($input, $this->taxClasses);
    }
}
