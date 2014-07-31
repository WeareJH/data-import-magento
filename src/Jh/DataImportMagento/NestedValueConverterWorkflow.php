<?php

namespace Jh\DataImportMagento;

use Ddeboer\DataImport\ValueConverter\ValueConverterInterface;
use Ddeboer\DataImport\Workflow;

/**
 * Class NestedValueConverterWorkflow
 * @package Ddeboer\DataImport
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class NestedValueConverterWorkflow extends Workflow
{

    /**
     * Add a value converter to the workflow
     *
     * @param string|array            $field     Field
     * @param ValueConverterInterface $converter ValueConverter
     *
     * @return $this
     */
    public function addValueConverter($field, ValueConverterInterface $converter)
    {
        //allow to attach the same converter to multiple fields in one go
        if (is_array($field)) {
            $fields = $field;
            foreach ($fields as $field) {
                $this->valueConverters[$field][] = $converter;
            }
        } else {
            $this->valueConverters[$field][] = $converter;
        }

        return $this;
    }

    /**
     * Convert the item
     *
     * @param string $item Original item values
     *
     * @return array                   Converted item values
     * @throws UnexpectedTypeException
     */
    protected function convertItem($item)
    {
        foreach ($this->itemConverters as $converter) {
            $item = $converter->convert($item);
            if ($item && !(is_array($item) || ($item instanceof \ArrayAccess && $item instanceof \Traversable))) {
                throw new UnexpectedTypeException($item, 'false or array');
            }

            if (!$item) {
                return $item;
            }
        }

        if ($item && !(is_array($item) || ($item instanceof \ArrayAccess && $item instanceof \Traversable))) {
            throw new UnexpectedTypeException($item, 'false or array');
        }

        foreach ($this->valueConverters as $property => $converters) {

            //is this is targeting a nested field
            if (strpos($property, '/') !== false) {

                $properties = explode('/', $property);
                $item = $this->recursivelyConvertValues($properties, $item, $converters);

            } else {
                $item = $this->recursivelyConvertValues([$property], $item, $converters);
            }
        }

        return $item;
    }

    /**
     * Recursively run value converters on nested data
     *
     * @param array $properties
     * @param array $data
     * @param array $converters
     * @return array
     */
    protected function recursivelyConvertValues(array $properties, array $data, array $converters)
    {
        $property = array_shift($properties);

        if (!count($properties)) {
            //this is the deepest field

            //This is an associative array
            if (isset($data[$property]) || array_key_exists($property, $data)) {
                foreach ($converters as $converter) {
                    $data[$property] = $converter->convert($data[$property]);
                }
            //This is a nested array
            } else {
                //try looping nested arrays
                foreach ($data as $key => $nestedItem) {
                    foreach ($converters as $converter) {
                        if (isset($nestedItem[$property]) || array_key_exists($property, $nestedItem)) {
                            $data[$key][$property] = $converter->convert($nestedItem[$property]);
                        }
                    }
                }
            }
        } else {
            //this is an associative array - so call self
            if (isset($data[$property])) {
                $data[$property] = $this->recursivelyConvertValues($properties, $data[$property], $converters);
            //this is a nested array so call self with each nested array
            } else {
                foreach ($data as $key => $item) {
                    $data[$key] = $this->recursivelyConvertValues($properties, $item, $converters);
                }
            }
        }

        return $data;
    }
}
