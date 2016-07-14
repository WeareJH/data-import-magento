<?php

namespace Jh\DataImportMagento\Reader;

use AydinHassan\XmlFuse\XmlFuse;
use AydinHassan\XmlFuse\XmlNest;
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
     * @param string $type
     */
    public function __construct($stream, array $xPaths = array(), $type = 'nest')
    {
        if (!is_resource($stream) || 'stream' !== get_resource_type($stream)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected argument to be a stream resource, got "%s"',
                    is_object($stream) ? get_class($stream) : gettype($stream)
                )
            );
        }

        $xml = stream_get_contents($stream);
        try {
            $parser = XmlFuse::factory($type, $xml, $xPaths);
        } catch (\UnexpectedValueException $e) {
            throw new ReaderException($e->getMessage(), 0, $e);
        }

        parent::__construct($parser->parse());
    }
}
