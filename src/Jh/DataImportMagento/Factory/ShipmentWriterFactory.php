<?php

namespace Jh\DataImportMagento\Factory;

use Jh\DataImportMagento\Writer\ShipmentWriter;

/**
 * Class ProductWriterFactory
 * @package Jh\DataImportMagento\Factory
 * @author  Anthony Bates <anthony@wearejh.com>
 */
class ShipmentWriterFactory
{
    /**
     * @return ShipmentWriter
     */
    public function __invoke()
    {
        $orderModel           = \Mage::getModel('sales/order');
        $transaction          = \Mage::getModel('core/resource_transaction');
        $trackingModel        = \Mage::getModel('sales/order_shipment_track');
        $options              = $options = [
            'send_shipment_email' => (bool) \Mage::getStoreConfig('sales_email/shipment/enabled')
        ];

        return new ShipmentWriter(
            $orderModel,
            $transaction,
            $trackingModel,
            $options
        );
    }
}
