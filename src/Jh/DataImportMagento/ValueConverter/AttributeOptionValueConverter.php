<?php

namespace Jh\DataImportMagento\ValueConverter;

use Ddeboer\DataImport\ValueConverter\ValueConverterInterface;
use Ddeboer\DataImport\Exception\UnexpectedValueException;

/**
 * Load the real Option Label for a given ID
 *
 * Class AttributeOptionValueConverter
 * @package Jh\DataImportMagento\ValueConverter
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class AttributeOptionValueConverter implements ValueConverterInterface
{
    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var string|null
     */
    protected $attributeCode = null;

    /**
     * @param array $options
     */
    public function __construct($attributeCode, $options = array())
    {
        $this->attributeCode    = $attributeCode;
        $this->options          = $options;
    }

    /**
     * @param mixed $input
     * @return mixed
     * @throws UnexpectedValueException
     */
    public function convert($input)
    {
        if(!array_key_exists($input, $this->options)) {
            throw new UnexpectedValueException(
                sprintf(
                    '"%s" does not appear to be a valid attribute option for "%s"',
                    $input,
                    $this->attributeCode
                )
            );
        }
        //look up the real option value
        return $this->options[$input];
    }
}
