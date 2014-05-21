<?php

namespace Jh\DataImportMagentoTest\Writer;

use Jh\DataImportMagento\Writer\CustomerWriter;

/**
 * Class CustomerWriterTest
 * @package Jh\DataImportMagentoTest\Writer
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class CustomerWriterTest extends \PHPUnit_Framework_TestCase
{

    protected $customerWriter;
    protected $customerModel;

    public function setUp()
    {
        $this->customerModel    = $this->getMock('\Mage_Customer_Model_Customer');
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

    public function testCustomerIsSavedWithAddressWhichHasRegionId()
    {
        $addressData = array(
            'street'        => 'Pilcher Gate',
            'town'          => 'Nottingham',
            'country_id'    => 'UK',
            'firstname'     => 'Aydin',
            'lastname'      => 'Hassan',
            'region'        => 'Nottinghamshire',
        );

        $data = array(
            'firstname' => 'Aydin',
            'lastname'  => 'Hassan',
            'address'   => array(
                $addressData
            ),
        );

        $name = $data['firstname'] . " " . $data['lastname'];

        $customerData = array(
            'firstname' => 'Aydin',
            'lastname'  => 'Hassan',
        );

        $this->customerModel
            ->expects($this->once())
            ->method('setData')
            ->with($customerData);

        $addressModel = $this->getMock('\Mage_Customer_Model_Address');

        $directoryResourceModel  = $this->getMockBuilder('\Mage_Directory_Model_Resource_Region_Collection')
            ->disableOriginalConstructor()
            ->getMock();

        $args = array($this->customerModel, $addressModel, $directoryResourceModel);
        $methods = array('lookUpRegion', 'processRegions');
        $this->customerWriter = $this->getMock('Jh\DataImportMagento\Writer\CustomerWriter', $methods, $args);

        /*$this->customerWriter
             ->expects($this->once())
             ->method('processRegions')
             ->with($directoryResourceModel)
             ->will($this->returnValue(array()));*/

        $this->customerWriter
             ->expects($this->once())
             ->method('lookUpRegion')
             ->with($addressData['region'], $addressData['country_id'], $name)
             ->will($this->returnValue(1));

        unset($addressData['region']);
        $addressData['region_id'] = 1;
        $addressModel
            ->expects($this->once())
            ->method('setData')
            ->with($addressData);

        $this->customerModel
             ->expects($this->once())
             ->method('addAddress')
             ->with($addressModel);

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

    public function testCustomerIsSavedWithAddressWhichHasNoRegionId()
    {
        $addressData = array(
            'street'        => 'Pilcher Gate',
            'town'          => 'Nottingham',
            'country_id'    => 'UK',
            'firstname'     => 'Aydin',
            'lastname'      => 'Hassan',
            'region'        => 'Nottinghamshire',
        );

        $data = array(
            'firstname' => 'Aydin',
            'lastname'  => 'Hassan',
            'address'   => array(
                $addressData
            ),
        );

        $name = $data['firstname'] . " " . $data['lastname'];

        $customerData = array(
            'firstname' => 'Aydin',
            'lastname'  => 'Hassan',
        );

        $this->customerModel
            ->expects($this->once())
            ->method('setData')
            ->with($customerData);

        $addressModel = $this->getMock('\Mage_Customer_Model_Address');

        $directoryResourceModel  = $this->getMockBuilder('\Mage_Directory_Model_Resource_Region_Collection')
            ->disableOriginalConstructor()
            ->getMock();

        $args = array($this->customerModel, $addressModel, $directoryResourceModel);
        $methods = array('lookUpRegion', 'processRegions');
        $this->customerWriter = $this->getMock('Jh\DataImportMagento\Writer\CustomerWriter', $methods, $args);

        /*$this->customerWriter
             ->expects($this->once())
             ->method('processRegions')
             ->with($directoryResourceModel)
             ->will($this->returnValue(array()));*/

        $this->customerWriter
            ->expects($this->once())
            ->method('lookUpRegion')
            ->with($addressData['region'], $addressData['country_id'], $name)
            ->will($this->returnValue(false));

        $addressModel
            ->expects($this->once())
            ->method('setData')
            ->with($addressData);

        $this->customerModel
            ->expects($this->once())
            ->method('addAddress')
            ->with($addressModel);

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



    protected function getMockRegionModel(array $data)
    {
        $iteration = 0;

        $model = $this->getMock('\Mage_Directory_Model_Region', array('getIdField', 'getData', 'getId'));

        $model->expects($this->at($iteration++))
            ->method('getData')
            ->with('country_id')
            ->will($this->returnValue($data['country_id']));

        $model->expects($this->at($iteration++))
            ->method('getData')
            ->with('name')
            ->will($this->returnValue($data['name']));

        $model->expects($this->at($iteration))
            ->method('getId')
            ->will($this->returnValue($data['id']));

        return $model;
    }


    public function testRegions()
    {
        $directoryResourceModel  = $this->getMockBuilder('\Mage_Directory_Model_Resource_Region_Collection')
            ->disableOriginalConstructor()
            ->getMock();

        $region1 = $this->getMockRegionModel(array('country_id' => 'UK', 'name' => 'Nottinghamshire',   'id' => 1));
        $region2 = $this->getMockRegionModel(array('country_id' => 'US', 'name' => 'Oregon',            'id' => 2));
        $region3 = $this->getMockRegionModel(array('country_id' => 'US', 'name' => 'California',        'id' => 3));

        $regions = new \ArrayIterator(array(
            $region1,
            $region2,
            $region3,
        ));

        $directoryResourceModel
            ->expects($this->once())
            ->method('getIterator')
            ->will($this->returnValue($regions));

        $this->customerWriter  = $this->getMockBuilder('Jh\DataImportMagento\Writer\CustomerWriter')
            ->disableOriginalConstructor()
            ->setMethods(array('__construct'))
            ->getMock();

        $expected = array(
            'UK' => array(
               'nottinghamshire' => 1,
            ),
            'US' => array(
                'oregon'        => 2,
                'california'    => 3,
            ),
        );

        $processed = $this->customerWriter->processRegions($directoryResourceModel);
        $this->assertEquals($expected, $processed);
    }

    public function testLookUpRegion()
    {
        $this->customerWriter  = $this->getMockBuilder('Jh\DataImportMagento\Writer\CustomerWriter')
            ->disableOriginalConstructor()
            ->setMethods(array('__construct'))
            ->getMock();

        $regions = array(
            'UK' => array(
                'nottinghamshire' => 1,
            ),
            'US' => array(
                'oregon'        => 2,
                'california'    => 3,
            ),
        );


        $refObject   = new \ReflectionObject($this->customerWriter);
        $refProperty = $refObject->getProperty('regions');
        $refProperty->setAccessible(true);
        $refProperty->setValue($this->customerWriter, $regions);

        $this->assertEquals(1, $this->customerWriter->lookUpRegion('nottinghamshire', 'UK', 'Some Name'));
        $this->assertEquals(2, $this->customerWriter->lookUpRegion('oregon', 'US', 'Some Name'));
        $this->assertEquals(3, $this->customerWriter->lookUpRegion('california', 'US', 'Some Name'));
        $this->assertEquals(false, $this->customerWriter->lookUpRegion('california', 'UK', 'Some Name'));
        $this->assertEquals(false, $this->customerWriter->lookUpRegion('california', 'AU', 'Some Name'));
    }

    public function testProcessRegionsIsCalledIfAddressModelIsPresent()
    {
        $this->customerWriter  = $this->getMockBuilder('Jh\DataImportMagento\Writer\CustomerWriter')
            ->disableOriginalConstructor()
            ->setMethods(array('processRegions'))
            ->getMock();

        $directoryResourceModel  = $this->getMockBuilder('\Mage_Directory_Model_Resource_Region_Collection')
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerWriter
            ->expects($this->once())
            ->method('processRegions')
            ->with($directoryResourceModel)
            ->will($this->returnValue(array()));

        $addressModel = $this->getMock('\Mage_Customer_Model_Address');

        $this->customerWriter->__construct($this->customerModel, $addressModel, $directoryResourceModel);
    }

    public function testMagentoSaveExceptionIsThrownIfSaveFails()
    {
        $data = array(
            'firstname' => 'Aydin',
            'lastname'  => 'Hassan',
            'email'     => 'aydin@hotmail.co.uk'
        );

        $this->customerModel
            ->expects($this->once())
            ->method('setData')
            ->with($data);

        $e = new \Mage_Customer_Exception("Save Failed");
        $this->customerModel
            ->expects($this->once())
            ->method('save')
            ->will($this->throwException($e));

        $this->customerModel
            ->expects($this->once())
            ->method('getPrimaryAddresses')
            ->will($this->returnValue(array()));

        $this->customerModel
            ->expects($this->once())
            ->method('getAdditionalAddresses')
            ->will($this->returnValue(array()));

        $this->setExpectedException('Jh\DataImportMagento\Exception\MagentoSaveException', 'Save Failed');
        $this->customerWriter->writeItem($data);
    }
}
