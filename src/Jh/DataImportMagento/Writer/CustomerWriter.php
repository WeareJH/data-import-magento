<?php
namespace Jh\DataImportMagento\Writer;

use Ddeboer\DataImport\Writer\AbstractWriter;

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
    private $customerModel;

    /**
     * @var Mage_Customer_Model_Address
     */
    private $addressModel;

    /**
     * @param Mage_Customer_Model_Customer $customerModel
     * @param Mage_Customer_Model_Address $addressModel
     */
    public function __construct(
        \Mage_Customer_Model_Customer $customerModel,
        \Mage_Customer_Model_Address $addressModel = null
    ) {
        $this->customerModel    = $customerModel;
        $this->addressModel     = $addressModel;
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
                $address = clone $this->addressModel;
                $address->setData($addressData);
                $customer->addAddress($address);
            }
        }

        $customer->save();
    }
}
