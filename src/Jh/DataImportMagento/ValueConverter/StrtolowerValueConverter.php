<?php

namespace Jh\DataImportMagento\ValueConverter;

use Ddeboer\DataImport\Exception\UnexpectedTypeException;
use Ddeboer\DataImport\ValueConverter\ValueConverterInterface;

/**
 * Class StrtolowerValueConverter
 * @package Jh\DataImportMagento\ValueConverter
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class StrtolowerValueConverter implements ValueConverterInterface
{

    /**
     * @param string $input
     * @return string
     * @throws UnexpectedTypeException
     */
    public function convert($input)
    {
        if (!is_string($input)) {
            throw new UnexpectedTypeException($input, 'string');
        }

        return strtolower($input);
    }
}
