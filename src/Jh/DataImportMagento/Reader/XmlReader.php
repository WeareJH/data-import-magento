<?php

namespace Jh\DataImportMagento\Reader;

use AydinHassan\XmlFuse\XmlFuse;
use Ddeboer\DataImport\Exception\ReaderException;
use Ddeboer\DataImport\Reader\ArrayReader;

/**
 * Class XmlReader
 * @package Jh\DataImportMagento\Reader
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class XmlReader extends ArrayReader
{

    /**
     * @param \SplFileObject $file
     * @param array $xPaths
     */
    public function __construct(\SplFileObject $file, array $xPaths = array())
    {
        $fileContents   = file_get_contents($file->getPathname());
        try {
            $xmlFuse = new XmlFuse($fileContents, $xPaths);
        } catch (\UnexpectedValueException $e) {
            throw new ReaderException($e->getMessage(), 0, $e);
        }

        parent::__construct($xmlFuse->parse());
    }
}
