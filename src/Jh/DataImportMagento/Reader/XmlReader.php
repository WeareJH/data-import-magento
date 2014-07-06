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
     * @param array $stream
     * @param array $xPaths
     */
    public function __construct($stream, array $xPaths = array())
    {
        if (!is_resource($stream) || !'stream' == get_resource_type($stream)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expects argument to be a stream resource, got %s',
                    is_resource($stream) ? get_resource_type($stream) : gettype($stream)
                )
            );
        }

        $xml = stream_get_contents($stream);
        try {
            $xmlFuse = new XmlFuse($xml, $xPaths);
        } catch (\UnexpectedValueException $e) {
            throw new ReaderException($e->getMessage(), 0, $e);
        }

        parent::__construct($xmlFuse->parse());
    }
}
