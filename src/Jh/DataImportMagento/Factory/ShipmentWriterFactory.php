<?php

namespace Jh\DataImportMagento\Factory;

use Jh\DataImportMagento\Writer\ShipmentWriter;
use Psr\Log\LoggerInterface;

/**
 * Class ProductWriterFactory
 * @package Jh\DataImportMagento\Factory
 * @author  Anthony Bates <anthony@wearejh.com>
 */
class ShipmentWriterFactory
{
    /**
     * @param LoggerInterface $logger
     * @return ShipmentWriter
     */
    public function __invoke(LoggerInterface $logger)
    {
        $orderModel           = \Mage::getModel('sales/order');
        $transaction          = \Mage::getModel('core/resource_transaction');
        $trackingModel        = \Mage::getModel('sales/order_shipment_track');
        $emailFlag            = \Mage::getStoreConfig('sales_email/shipment/enabled');
        $options              = [
            'emailFlag' => $emailFlag
        ];

        return new ShipmentWriter(
            $orderModel,
            $transaction,
            $trackingModel,
            $options,
            $logger
        );
    }
}
