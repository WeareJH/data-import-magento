<?php

namespace Jh\DataImportMagento\ItemConverter;

use Ddeboer\DataImport\ItemConverter\MappingItemConverter;

/**
 * Class MappingConverter
 * @package Jh\DataImportMagento\ItemConverter
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class MappingConverter extends MappingItemConverter
{

    /**
     * @var MappingItemConverter
     */
    private $mappingItemConverters;

    private $mappings = [];

    const NEST_MAP_KEY = '___NESTED___';

    public function __construct(array $mappings)
    {
        $this->mappingItemConverters = $mappings;

        $mappings = [
            'map1'      => 'new1',
            'nested'    => [
                '___NESTED___'  => true,
                'map2'          => 'new2',
                'map3'          => 'new3'
            ]
        ];

        foreach ($mappings as $from => $to) {
            if (is_array($to) && $to[static::NEST_MAP_KEY] === true) {
                $this->mappings[$from] = new self($to);
            } else {
                $this->mappings[$from] = $to;
            }
        }

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
        foreach ($this->mappings as $from => $to) {

            if ($to instanceof self) {
                foreach ($from as $key => $row) {
                    $input[$key] = $to->convert($row);
                }
            } else {
                $input = $this->applyMapping($input, $from, $to);
            }
        }

        return $input;
    }

    /**
     * @param string       $from
     * @param array|string $to
     *
     * @return $this
     */
    public function addMapping($from, $to)
    {
        $this->mappings[$from] = $to;
        return $this;
    }

    /**
     * @param string               $from
     * @param MappingItemConverter $mappingConverter
     *
     * @return $this
     */
    public function addNestedMapping($from, MappingItemConverter $mappingConverter)
    {
        $this->mappings[$from] = $mappingConverter;
        return $this;
    }
}