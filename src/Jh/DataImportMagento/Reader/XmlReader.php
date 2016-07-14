<?php

namespace Jh\DataImportMagento\Reader;

use AydinHassan\XmlFuse\XmlFuse;
use AydinHassan\XmlFuse\XmlNest;
use Ddeboer\DataImport\Exception\ReaderException;
use Ddeboer\DataImport\Reader\ArrayReader;
use Ddeboer\DataImport\Reader\ReaderInterface;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class XmlReader implements ReaderInterface
{
    /**
     * @var resource
     */
    private $stream;

    /**
     * @var array
     */
    private $xPaths;

    /**
     * @var string
     */
    private $xmlParseType;

    /**
     * @var ArrayReader|null
     */
    private $innerReader;

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

        $this->stream = $stream;
        $this->xmlParseType = $type;
        $this->xPaths = $xPaths;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $this->init();
        return $this->innerReader->current();
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->init();
        $this->innerReader->next();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->innerReader->key();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        $this->init();
        return $this->innerReader->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->init();
        $this->innerReader->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function getFields()
    {
        $this->init();
        return $this->innerReader->getFields();
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        $this->init();
        return $this->innerReader->count();
    }

    /**
     * Perform the parsing and initialise the inner reader
     * if it has not been done so already.
     */
    private function init()
    {
        if (null !== $this->innerReader) {
            return;
        }

        $xml = stream_get_contents($this->stream);
        try {
            $parser = XmlFuse::factory($this->xmlParseType, $xml, $this->xPaths);
        } catch (\UnexpectedValueException $e) {
            throw new ReaderException($e->getMessage(), 0, $e);
        }

        $this->innerReader = new ArrayReader($parser->parse());
    }
}
