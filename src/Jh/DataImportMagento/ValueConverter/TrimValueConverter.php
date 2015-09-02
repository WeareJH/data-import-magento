<?php

namespace Jh\DataImportMagento\ValueConverter;

use Ddeboer\DataImport\Exception\UnexpectedTypeException;
use Ddeboer\DataImport\ValueConverter\ValueConverterInterface;

/**
 * Class TrimValueConverter
 * @package Jh\DataImportMagento\ValueConverter
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class TrimValueConverter implements ValueConverterInterface
{

    /**
     * @var string
     */
    private $charMask;

    /**
     * @param $charMask
     */
    public function __construct($charMask = ' \t\n\r\0\x0B')
    {
        $this->charMask = $charMask;
    }

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

        return trim($input, $this->charMask);
    }
}
