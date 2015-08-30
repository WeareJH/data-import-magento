<?php
namespace Jh\DataImportMagento\Writer;

use Ddeboer\DataImport\Exception\WriterException;
use Ddeboer\DataImport\Writer\AbstractWriter;
use Jh\DataImportMagento\Exception\MagentoSaveException;

/**
 * Class ShipmentWriter
 * @package Jh\DataImportMagento\Writer
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ShipmentWriter extends AbstractWriter
{
    /**
     * @var \Mage_Core_Model_Resource_Transaction
     */
    protected $transactionResourceModel;

    /**
     * @var \Mage_Sales_Model_Order
     */
    protected $orderModel;

    /**
     * @param \Mage_Core_Model_Resource_Transaction $transactionResourceModel
     * @param \Mage_Sales_Model_Order $order
     */
    public function __construct(
        \Mage_Core_Model_Resource_Transaction $transactionResourceModel,
        \Mage_Sales_Model_Order $order
    ) {
        $this->transactionResourceModel = $transactionResourceModel;
        $this->orderModel               = $order;
    }

    /**
     * @param array $item
     * @return \Ddeboer\DataImport\Writer\WriterInterface|void
     * @throws \Ddeboer\DataImport\Exception\WriterException
     * @throws \Jh\DataImportMagento\Exception\MagentoSaveException
     */
    public function writeItem(array $item)
    {
        if (!isset($item['order_id'])) {
            throw new WriterException('order_id must be set');
        }

        $order = clone $this->orderModel;
        $order->loadByIncrementId($item['order_id']);

        if (!$order->getId()) {
            throw new WriterException(sprintf('Order with ID: "%s" cannot be found', $item['order_id']));
        }

        try {
            $shipment = $order->prepareShipment();
            $shipment->register();
            $shipment->getOrder()->setData('is_in_process', true);

            $transactionSave = clone $this->transactionResourceModel;
            $transactionSave
                ->addObject($shipment)
                ->addObject($shipment->getOrder())
                ->save();

        } catch (\Exception $e) {
            throw new MagentoSaveException($e->getMessage());
        }
    }
}
