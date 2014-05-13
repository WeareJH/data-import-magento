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
     * @param array $mappings
     * @param string $resultKey
     */
    public function __construct(array $mappings, $resultKey)
    {
        $this->setMappings($mappings);
        $this->resultKey = $resultKey;
    }

    /**
     * {@inheritdoc}
     */
    public function convert($input)
    {

        if (isset($input[$this->resultKey])) {
            throw new InvalidArgumentException($this->resultKey . " is already set");
        }

        $input[$this->resultKey] = array();

        $data = array();
        foreach ($this->mappings as $from => $remove) {

            $data[$from] = $input[$from];

            if ($remove) {
                unset($input[$from]);
            }
        }
        $input[$this->resultKey][] = $data;
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
                if (isset($mapping[1])) {
                    if (!is_bool($mapping[1])) {
                        throw new InvalidArgumentException(
                            "Second Argument should be an boolean value - whether to remove the value from parent row"
                        );
                    }
                } else {
                    $mapping[1] = true;
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
