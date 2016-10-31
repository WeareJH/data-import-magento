<?php

namespace Jh\DataImportMagentoTest\Writer;

use Jh\DataImportMagento\Writer\CsvWriter;
use PHPUnit_Framework_TestCase;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class CsvWriterTest extends PHPUnit_Framework_TestCase
{
    private $tempFile;

    public function setUp()
    {
        $this->tempFile = sprintf('%s/%s.csv', sys_get_temp_dir(), uniqid($this->getName(), true));
    }

    public function tearDown()
    {
        unlink($this->tempFile);
    }

    public function testEmptyFieldsAreNotWrappedInEnclosureWhenOptionIsSetToFalse()
    {
        $writer = new CsvWriter(new \SplFileObject($this->tempFile, 'w+'), 'w+', ',', '"', "\n", true);
        $writer->writeItem(['one', 'two', '', 'four']);
        $writer->finish();

        $this->assertEquals("\"one\",\"two\",\"\",\"four\"\n", file_get_contents($this->tempFile));
    }

    public function testEmptyFieldsAreNotWrappedInEnclosureByDefault()
    {
        $writer = new CsvWriter(new \SplFileObject($this->tempFile, 'w+'), 'w+', ',', '"', "\n");
        $writer->writeItem(['one', 'two', '', 'four']);
        $writer->finish();

        $this->assertEquals("\"one\",\"two\",,\"four\"\n", file_get_contents($this->tempFile));
    }
}
