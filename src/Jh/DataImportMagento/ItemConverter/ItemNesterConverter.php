<?php

namespace Jh\DataImportMagento\ItemConverter;

use InvalidArgumentException;
use Ddeboer\DataImport\ItemConverter\ItemConverterInterface;

/**
 * Class ItemNesterConverter
 * @package Ddeboer\DataImport\ItemConverter
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ItemNesterConverter implements ItemConverterInterface
{
    /**
     * @var array
     */
    protected $mappings = array();

    /**
     * @var string
     */
    protected $resultKey;

    /**
     * Whether to nest in an array or a direct nest
     * Array nest would look like 'address' => [[ Addr1 ], [Addr2]]
     * Normal nest would look like 'address' => ['Steet1' => 'Street', 'City' => 'Notss]
     *
     * @var bool
     */
    protected $arrayNest = true;

    /**
     * @param array $mappings
     * @param string $resultKey
     * @param bool
     */
    public function __construct(array $mappings, $resultKey, $array = true)
    {
        $this->setMappings($mappings);
        $this->resultKey = $resultKey;
        $this->arrayNest = $array;
    }

    /**
     * {@inheritdoc}
     */
    public function convert($input)
    {

        if (isset($input[$this->resultKey])) {
            throw new InvalidArgumentException("'$this->resultKey' is already set");
        }

        $input[$this->resultKey] = array();

        $data = array();
        foreach ($this->mappings as $from => $remove) {
            if (isset($input[$from])) {
                $data[$from] = $input[$from];
            } else {
                $data[$from] = null;
            }


            if ($remove) {
                unset($input[$from]);
            }
        }

        if ($this->arrayNest) {
            $input[$this->resultKey][] = $data;
        } else {
            $input[$this->resultKey] = $data;

        }

        return $input;
    }

    /**
     *
     * @param array $mappings
     */
    public function setMappings(array $mappings)
    {
        $processedMappings = array();
        foreach ($mappings as $mapping) {
            if (!is_array($mapping)) {
                $processedMappings[$mapping] = true;
            } else {
                $field = key($mapping);
                $value = $mapping[$field];

                if (!is_bool($value)) {
                    $mapping = array($field, true);
                } else {
                    $mapping = array($field, $value);
                }

                $processedMappings[$mapping[0]] = $mapping[1];
            }
        }

        $this->mappings = $processedMappings;
    }

    /**
     * @return array
     */
    public function getMappings()
    {
        return $this->mappings;
    }
}
