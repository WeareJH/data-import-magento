<?php

namespace Jh\DataImportMagentoTest\Factory;

use Jh\DataImportMagento\Factory\ShipmentWriterFactory;

/**
 * Class ShipmentWriterFactoryTest
 * @package Jh\DataImportMagentoTest\Factory
 * @author  Anthony Bates <anthony@wearejh.com>
 */
class ShipmentWriterFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testFactoryReturnsInstance()
    {
        $factory = new ShipmentWriterFactory();
        $this->assertInstanceOf('\Jh\DataImportMagento\Writer\ShipmentWriter', $factory->__invoke(
            $this->getMock('\Psr\Log\LoggerInterface')
        ));
    }
}
