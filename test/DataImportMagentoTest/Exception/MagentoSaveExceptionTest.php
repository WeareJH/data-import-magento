<?php

namespace Jh\DataImportMagentoTest\Exception;

use Jh\DataImportMagento\Exception\MagentoSaveException;

/**
 * Class MagentoSaveExceptionTest
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class MagentoSaveExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $e = new MagentoSaveException('Some Message');
        $this->assertSame('Some Message', $e->getMessage());
        $this->assertSame(0, $e->getCode());
    }
}
