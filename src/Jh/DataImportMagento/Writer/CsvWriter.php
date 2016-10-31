<?php

namespace Jh\DataImportMagento\Writer;

use Ddeboer\DataImport\Exception\ReaderException;
use Ddeboer\DataImport\Writer\AbstractWriter;

/**
 * CSV Writer which provides more
 * options than the default
 *
 * Will also write headers and be strict with import
 * data. Expects data to be key => value pairs
 *
 * Class CsvWriter
 * @package Jh\DataImportMagento\Writer
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class CsvWriter extends AbstractWriter
{
    /**
     * CSV Delimiter
     *
     * @var string
     */
    protected $delimiter = ';';

    /**
     * CSV Enclosure.
     * fputcsv only encloses values
     * it deems need to be enclosed
     *
     * @var string
     */
    protected $enclosure = '"';

    /**
     * EOL because some people use
     * windows for batch processing???!
     *
     * @var string
     */
    protected $eol = "\n";

    /**
     * File pointer
     *
     * @var null|resource
     */
    protected $fp = null;

    /**
     * Column Headers
     *
     * @var null|array
     */
    protected $columnHeaders = null;

    /**
     * Count of headers
     *
     * @var int
     */
    protected $headersCount;

    /**
     * @var bool
     */
    private $encloseEmptyFields = false;

    /**
     *
     * @param \SplFileObject $file CSV file
     * @param string $mode See http://php.net/manual/en/function.fopen.php
     * @param string $delimiter The delimiter
     * @param string $enclosure The enclosure
     * @param string $eol The end of line string
     * @param bool $encloseEmptyFields Whether to enclose empty fields or not
     */
    public function __construct(\SplFileObject $file, $mode = 'w', $delimiter = ';', $enclosure = '"', $eol = "\n", $encloseEmptyFields = false)
    {
        $this->fp                 = fopen($file->getPathname(), $mode);
        $this->delimiter          = $delimiter;
        $this->enclosure          = $enclosure;
        $this->eol                = $eol;
        $this->encloseEmptyFields = $encloseEmptyFields;
    }

    /**
     * Set column headers
     *
     * @param array $columnHeaders
     *
     * @return CsvReader
     */
    public function setColumnHeaders(array $columnHeaders)
    {
        $this->columnHeaders = $columnHeaders;
        $this->headersCount = count($columnHeaders);

        return $this;
    }

    /**
     * Write Column Header
     *
     * @return \Ddeboer\DataImport\Writer\WriterInterface
     */
    public function prepare()
    {
        if (null !== $this->columnHeaders) {
            $this->writeLine($this->columnHeaders);
        }

        return $this;
    }

    /**
     * TODO: Make stripping quotes/enclosure configurable
     * TODO: Make enclosing configurable
     * TODO: Use fputcsv when ALL_ENCLOSING not necessary
     *
     * Write a line, removing double quotes and then enclosing every
     * piece of data.
     *
     * @param array $data
     */
    public function writeLine(array $data)
    {
        $line = implode($this->delimiter, array_map(function ($string) {
            $string = str_replace('"', '', $string);

            if (!$this->encloseEmptyFields && empty($string) && $string !== '0') {
                //if the string is empty don't quote it
                return $string;
            }

            return sprintf('%s%s%s', $this->enclosure, $string, $this->enclosure);
        }, $data));

        fputs($this->fp, $line . $this->eol);
    }

    /**
     * TODO: Make ordering configurable
     *
     * {@inheritdoc}
     */
    public function writeItem(array $item)
    {
        if (null !== $this->columnHeaders) {
            if ($this->headersCount !== count($item)) {
                throw new ReaderException("Row contains a different amount of items to headers");
            }

            $item = $this->orderDataByColumnHeaders($item);
        }

        $this->writeLine($item);
    }

    /**
     * Order the data using the order of the Header rows
     * This assumes that each row has the keys which
     * correspond to the header
     *
     * @param array $data
     * @return array
     */
    public function orderDataByColumnHeaders(array $data)
    {
        $correctOrder = array_merge(array_flip($this->columnHeaders), $data);
        return array_values($correctOrder);
    }

    /**
     * {@inheritdoc}
     */
    public function finish()
    {
        fclose($this->fp);
    }
}
