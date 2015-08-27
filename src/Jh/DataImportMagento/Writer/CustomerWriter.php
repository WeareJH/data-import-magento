<?php
namespace Jh\DataImportMagento\Writer;

use Ddeboer\DataImport\Writer\AbstractWriter;
use Jh\DataImportMagento\Exception\MagentoSaveException;

/**
 * Class MagentoCustomerWriter
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 * @package Jh\DataImportMagento\Writer
 */
class CustomerWriter extends AbstractWriter
{

    /**
     * @var Mage_Customer_Model_Customer
     */
    protected $customerModel;

    /**
     * @var Mage_Customer_Model_Address
     */
    protected $addressModel;

    /**
     * @var array
     */
    protected $regions = null;

    /**
     * @var array
     */
    protected $regionLookUpErrors = array();

    /**
     * @param \Mage_Customer_Model_Customer $customerModel
     * @param \Mage_Customer_Model_Address $addressModel
     * @param \Mage_Directory_Model_Resource_Region_Collection $regions
     */
    public function __construct(
        \Mage_Customer_Model_Customer $customerModel,
        \Mage_Customer_Model_Address $addressModel = null,
        \Mage_Directory_Model_Resource_Region_Collection $regions = null
    ) {
        $this->customerModel    = $customerModel;
        $this->addressModel     = $addressModel;

        //load countries and regions
        if ($this->addressModel) {
            $this->regions = $this->processRegions($regions);
        }

    }

    /**
     * @param array $item
     */
    public function writeItem(array $item)
    {
        $customer = clone $this->customerModel;

        //get address
        if (isset($item['address'])) {
            $addresses = $item['address'];
            unset($item['address']);
        }

        $customer->setData($item);

        //if we are adding addresses - create
        //model for each and set it on the customer
        if ($this->addressModel) {
            foreach ($addresses as $addressData) {
                //lookup region info:
                $name = '';
                if (isset($addressData['firstname']) && $addressData['lastname']) {
                    $name = $addressData['firstname'] . " " . $addressData['lastname'];
                }

                $regionId = false;
                if (isset($addressData['region']) && $addressData['country_id']) {
                    $regionId = $this->lookUpRegion($addressData['region'], $addressData['country_id'], $name);
                }

                if ($regionId) {
                    $addressData['region_id'] = $regionId;
                    unset($addressData['region']);
                }

                $address = clone $this->addressModel;

                $address->setData($addressData);
                $address->setIsDefaultShipping(true);
                $address->setIsDefaultBilling(true);
                $customer->addAddress($address);
            }
        }

        try {
            $customer->save();
        } catch (\Mage_Core_Exception $e) {
            $message = $e->getMessage();
            if (isset($item['email'])) {
                $message .= " : " . $item['email'];
            }

            throw new MagentoSaveException($message);
        }
    }

    /**
     * @param \Mage_Directory_Model_Resource_Region_Collection $regions
     * @return array
     */
    public function processRegions(\Mage_Directory_Model_Resource_Region_Collection $regions)
    {

        $sortedRegions = array();
        foreach ($regions as $region) {
            $countryId = $region->getData('country_id');
            if (!isset($sortedRegions[$countryId])) {
                $sortedRegions[$countryId] = array(
                    strtolower($region->getData('name')) => $region->getId()
                );
            } else {
                $sortedRegions[$countryId][strtolower($region->getData('name'))] = $region->getId();
            }
        }
        return $sortedRegions;
    }


    /**
     * @param string $regionText
     * @param string $countryId
     * @param string $name
     * @return int|bool
     */
    public function lookUpRegion($regionText, $countryId, $name)
    {
        //country requires pre-defined state
        if (isset($this->regions[$countryId])) {
            if (isset($this->regions[$countryId][strtolower($regionText)])) {
                return $this->regions[$countryId][strtolower($regionText)];
            } else {
                $this->regionLookUpErrors[]
                    = "Customer '$name' has region '$regionText' from country '$countryId'. NOT FOUND";
            }
        }

        return false;
    }
}
