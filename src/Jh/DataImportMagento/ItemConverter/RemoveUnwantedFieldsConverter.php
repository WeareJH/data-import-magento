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
        $this->fieldsTokeep = array_flip($fieldsTokeep);
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
        return array_intersect_key($input, $this->fieldsTokeep);
    }
}
