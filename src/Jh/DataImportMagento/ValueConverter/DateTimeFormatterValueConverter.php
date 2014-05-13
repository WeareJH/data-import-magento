<?php

namespace Jh\DataImportMagento\ValueConverter;

use Ddeboer\DataImport\ValueConverter\ValueConverterInterface;

/**
 * Convert an date string into another date string
 * Eg. You want to change the format of a string
 *
 * Class DateTimeFormatterValueConverter
 * @package Jh\DataImportMagento\ValueConverter
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class DateTimeFormatterValueConverter implements ValueConverterInterface
{
    /**
     * Date time format
     *
     * @var string
     * @see http://php.net/manual/en/datetime.createfromformat.php
     */
    protected $inputFormat;

    /**
     * Date time format
     *
     * @var string
     * @see http://php.net/manual/en/datetime.createfromformat.php
     */
    protected $outputFormat;

    /**
     * @param string $inputFormat
     * @param string $outputFormat
     */
    public function __construct($inputFormat = null, $outputFormat = null)
    {
        $this->inputFormat  = $inputFormat;
        $this->outputFormat = $outputFormat;
    }

    /**

     *
     * @param string $input
     *
     * @return \DateTime
     */


    /**
     * Convert string to date time object
     * + then convert back to a string
     * using specified format
     *
     * If no output format specified then return
     * the \DateTime instance
     *
     * @param mixed $input
     * @return \DateTime|string
     * @throws UnexpectedValueException
     */
    public function convert($input)
    {
        if (!$input) {
            return;
        }

        if ($this->inputFormat) {
            $date = \DateTime::createFromFormat($this->inputFormat, $input);
            if (false === $date) {
                throw new \UnexpectedValueException(
                    $input . ' is not a valid date/time according to format ' . $this->inputFormat);
            }
        } else {
            $date = new \DateTime($input);
        }

        if ($this->outputFormat) {
            return $date->format($this->outputFormat);
        }

        //if no output format specified we just return the \DateTime instance
        return $date;
    }
}
