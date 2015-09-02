<?php

namespace Jh\DataImportMagentoTest\Factory;

use Jh\DataImportMagento\Factory\ProductWriterFactory;

/**
 * Class ProductWriterFactoryTest
 * @package Jh\DataImportMagentoTest\Factory
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class ProductWriterFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testFactoryReturnsInstance()
    {
        $factory = new ProductWriterFactory;
        $this->assertInstanceOf('\Jh\DataImportMagento\Writer\ProductWriter', $factory->__invoke(
            $this->getMock('\Psr\Log\LoggerInterface')
        ));
    }
}
