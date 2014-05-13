<?php

namespace Jh\DataImportMagentoTest\Writer;

use Jh\DataImportMagento\Writer\CustomerWriter;

/**
 * Class CustomerWriterTest
 * @package Jh\DataImportMagentoTest\Writer
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class CustomerWriterTest extends \PHPUnit_Framework_TestCase
{

    protected $customerWriter;
    protected $customerModel;

    public function setUp()
    {
        $this->customerModel    = $this->getMock('Mage_Customer_Model_Customer');
        $this->customerWriter   = new CustomerWriter($this->customerModel);
    }

    public function testMagentoModelIsSaved()
    {
        $data = array(
            'firstname' => 'Aydin',
            'lastname'  => 'Hassan',
        );

        $this->customerModel
            ->expects($this->once())
            ->method('setData')
            ->with($data);

        $this->customerModel
            ->expects($this->once())
            ->method('save');

        $this->customerModel
            ->expects($this->once())
            ->method('getPrimaryAddresses')
            ->will($this->returnValue(array()));

        $this->customerModel
            ->expects($this->once())
            ->method('getAdditionalAddresses')
            ->will($this->returnValue(array()));


        $this->customerWriter->writeItem($data);
    }
}
