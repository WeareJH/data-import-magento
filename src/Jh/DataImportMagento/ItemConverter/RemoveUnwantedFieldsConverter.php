<?php

namespace Jh\DataImportMagento\ItemConverter;

use Ddeboer\DataImport\ItemConverter\ItemConverterInterface;

/**
 * Class RemoveUnwantedFieldsConverter
 * @package Jh\DataImportMagento\ItemConverter
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class RemoveUnwantedFieldsConverter implements ItemConverterInterface
{

    /**
     * @var array
     */
    protected $fieldsTokeep;

    /**
     * @param array $fieldsTokeep
     */
    public function __construct(array $fieldsTokeep)
    {
        //create an array of field to keep as the keys
        //they will have a value of ''
        $this->fieldsTokeep = array_fill_keys(
            array_keys(array_flip($fieldsTokeep)),
            ''
        );
    }

    /**
     * Convert an input
     *
     * @param mixed $input Input
     *
     * @return array|null the modified input or null to remove it
     */
    public function convert($input)
    {
        //remove keys not specified in $this->fieldsToKeep
        $values         = array_intersect_key($input, $this->fieldsTokeep);
        //check which values are missing from $values but are in $this->fieldsToKeep
        $missingFields  = array_diff_key($this->fieldsTokeep, $values);
        //merge missing fields with $values - using the default value specified in the constructor: ''
        $values         = array_merge($values, $missingFields);

        return $values;
    }
}
