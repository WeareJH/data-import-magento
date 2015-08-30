<?php

namespace Jh\DataImportMagento\ItemConverter;

use Ddeboer\DataImport\Exception\UnexpectedTypeException;
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
    protected $fieldsToKeep;

    /**
     * Value to fill non-present
     * but required fields
     *
     * @var string
     */
    protected $defaultValue = "";

    /**
     * @param array $fields
     */
    public function __construct(array $fields)
    {
        //check if there are nested mappings
        //if there are use the key as fieldName
        $fieldsToKeep = [];
        foreach ($fields as $key => $field) {
            if (is_array($field)) {
                $fieldsToKeep[$key]     = $field;
            } else {
                $fieldsToKeep[$field]   = $field;
            }
        }

        $this->fieldsToKeep = $fieldsToKeep;
    }

    /**
     * Remove unwanted fields according to mapping -
     * Also populates required values with a default value
     * Works one level deep with nested items.
     *
     * @param array $input
     * @return array
     */
    public function convert($input)
    {
        if (!is_array($input)) {
            throw new UnexpectedTypeException($input, 'array');
        }

        $return = [];

        //remove any fields not required
        foreach ($input as $key => $val) {
            if (is_array($val)) {
                $fieldsToKeep = $this->fieldsToKeep[$key];
                $return[$key] = array_map(function ($nestedItem) use ($fieldsToKeep) {
                    return array_intersect_key($nestedItem, array_flip($fieldsToKeep));
                }, $val);
            } else {
                if (array_key_exists($key, $this->fieldsToKeep)) {
                    $return[$key] = $val;
                }
            }
        }

        //add missing values
        foreach ($this->fieldsToKeep as $keyField => $valueField) {
            if (is_array($valueField)) {
                if (!isset($return[$keyField])) {
                    $return[$keyField] = [];
                } else {
                    if (!is_array($return[$keyField])) {
                        throw new UnexpectedTypeException($return[$keyField], 'array');
                    }

                    foreach ($valueField as $nestedField) {
                        $return[$keyField] = array_map(function ($item) use ($nestedField) {
                            if (!array_key_exists($nestedField, $item)) {
                                $item[$nestedField] = $this->defaultValue;
                            }
                            return $item;
                        }, $return[$keyField]);
                    }
                }

            } else {
                if (!array_key_exists($keyField, $return)) {
                    $return[$keyField] = $this->defaultValue;
                }
            }
        }

        return $return;
    }
}
