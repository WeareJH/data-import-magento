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
            switch($type) {
                case 'nest':
                    $xmlParser = new XmlNest($xml, $xPaths);
                    break;
                case 'merge':
                    $xmlParser = new XmlFuse($xml, $xPaths);
                    break;
                default:
                    throw new \InvalidArgumentException(
                        sprintf("'%s' is not a valid type. Valid types are 'nest', 'merge'", $type)
                    );
            }

        } catch (\UnexpectedValueException $e) {
            throw new ReaderException($e->getMessage(), 0, $e);
        }

        parent::__construct($xmlParser->parse());
    }
}
