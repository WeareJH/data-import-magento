<?php
namespace Jh\DataImportMagento\Writer;

use Ddeboer\DataImport\Exception\WriterException;
use Ddeboer\DataImport\Writer\AbstractWriter;
use Jh\DataImportMagento\Exception\MagentoSaveException;

/**
 * Class InvoiceWriter
 * @package Jh\DataImportMagento\Writer
 * @author Adam Paterson <adam@wearejh.com>
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class InvoiceWriter extends AbstractWriter
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

        /* @var $invoice Mage_Sales_Model_Order_Invoice */
        $invoice = $order->prepareInvoice();

        if (!$invoice->getData('total_qty')) {
            throw new WriterException(
                sprintf('Cannot create invoice without products. Order ID: "%s"', $order->getId())
            );
        }

        try {

            $invoice->register();
            $invoice->getOrder()->setIsInProcess(true);

            $invoice->setData('request_capture_case', 'offline');
            $transactionSave = clone $this->transactionResourceModel;
            $transactionSave
                ->addObject($invoice)
                ->addObject($invoice->getOrder())
                ->save();

        } catch (\Exception $e) {
            throw new MagentoSaveException($e->getMessage());
        }
    }
}
