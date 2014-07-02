<?php

namespace Jh\DataImportMagento\Reader;

use Ddeboer\DataImport\Exception\ReaderException;
use Ddeboer\DataImport\Reader\ReaderInterface;

/**
 * Class XmlReader
 * @package Jh\DataImportMagento\Reader
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class XmlReader implements ReaderInterface
{

    /**
     * @var \SplFileObject
     */
    protected $file;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var bool
     */
    protected $dataSent = false;

    /**
     * @param \SplFileObject $file
     */
    public function __construct(\SplFileObject $file)
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($file->getPathname());

        if (false === $xml) {
            throw new ReaderException(
                sprintf(
                    "Failed to parse file. Errors: '%s'",
                    implode(
                        "', '",
                        array_map(function ($error) {
                            return trim($error->message);
                        }, libxml_get_errors())
                    )
                )
            );
        }

        //hack to decode XML to array
        $this->data = json_decode(json_encode($xml), true);
    }

    /**
     * @return array
     */
    public function current()
    {
        $this->dataSent = true;
        return $this->data;
    }

    /**
     * @return void
     */
    public function next()
    {
    }

    /**
     * @return int
     */
    public function key()
    {
        return 0;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return !$this->dataSent;
    }

    /**
     * @return void
     */
    public function rewind()
    {
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return array_keys($this->data);
    }

    /**
     * @return int
     */
    public function count()
    {
        return 1;
    }
}
