<?php
namespace Jh\DataImportMagento\Writer;

use Ddeboer\DataImport\Exception\WriterException;
use Ddeboer\DataImport\Writer\AbstractWriter;
use Jh\DataImportMagento\Exception\MagentoSaveException;

/**
 * Class ReturnsWriter
 * @package Jh\DataImportMagento\Writer
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ReturnsWriter extends AbstractWriter
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
     * @var array
     */
    protected $options = [
        'order_id_field' => 'increment_id',
    ];

    /**
     * @param \Mage_Sales_Model_Order $orderModel
     * @param \Mage_Core_Model_Resource_Transaction $transactionResourceModel
     */
    public function __construct(
        \Mage_Sales_Model_Order $orderModel,
        \Mage_Core_Model_Resource_Transaction $transactionResourceModel
    ) {
        $this->orderModel                   = $orderModel;
        $this->transactionResourceModel     = $transactionResourceModel;
    }

    /**
     * @param array $item
     * @return \Ddeboer\DataImport\Writer\WriterInterface|void
     * @throws \Ddeboer\DataImport\Exception\WriterException
     * @throws \Jh\DataImportMagento\Exception\MagentoSaveException
     */
    public function writeItem(array $item)
    {
        if (!isset($item['orderId'])) {
            throw new WriterException('order_id must be set');
        }

        $order      = $this->getOrder($item['orderId']);
        $service    = $this->getServiceForOrder($order);

        $quantities         = $this->validateItemsToBeRefunded($order, $item['items']);
        $alreadyReturned    = $this->getItemsRefunded($order, $quantities);
        //TODO: Make this configurable - Some Returns will not include the total qty's returned
        //TODO: Just the exact qty to return.
        $returnQuantities    = $this->getActualRefundCount($alreadyReturned, $quantities);

        if (!count($returnQuantities)) {
            throw new WriterException(
                sprintf(
                    'Credit Memo cannot be created with no Items to Refund. Order ID: "%s"',
                    $item['orderId']
                )
            );
        }

        /** @var \Mage_Sales_Model_Order_Creditmemo $creditMemo */
        $creditMemo = $service->prepareCreditmemo([
            'qtys'              => $returnQuantities,
            //TODO: Make this configurable - have an option whether to refund shipping or not
            //TODO: if yes, then grab the amount from the input data
            'shipping_amount'   => 0,
        ]);

        //don't actually perform refund.
        //TODO: Make this configurable ^
        $creditMemo->addData([
            'offline_requested' => true,
        ]);

        if (isset($item['comment'])) {
            $creditMemo->addComment($item['comment']);
        }

        $creditMemo->register();

        try {
            $transactionSave = clone $this->transactionResourceModel;
            $transactionSave
                ->addObject($creditMemo)
                ->addObject($creditMemo->getOrder())
                ->save();

        } catch (\Exception $e) {
            throw new MagentoSaveException($e->getMessage());
        }
    }

    /**
     * @param int $orderId
     * @throws WriterException
     * @return \Mage_Sales_Model_Order
     */
    public function getOrder($orderId)
    {
        $order = clone $this->orderModel;
        $order->load($orderId, $this->options['order_id_field']);

        if (!$order->getId()) {
            throw new WriterException(
                sprintf(
                    'Cannot find order with id: "%s", using: "%s" as id field',
                    $orderId,
                    $this->options['order_id_field']
                )
            );
        }

        return $order;
    }

    /**
     * If we have an item which has a qty of 7 to be refunded. What this actually means is we
     * have refunded a total amount of 7, but part of that qty could have been refunded at an earlier time.
     * So we need to get the total of that item already refunded and minus it from the qty to be refunded.
     *
     * Imagine we receive an refund with qty of 7 to refund. We have already refunded 4 so we want to refund the
     * other 3. SO: QtyToRefund - AlreadyRefunded === ActualQtyToRefund.
     *
     * @param array $alreadyRefunded
     * @param array $toRefund
     * @return array
     */
    public function getActualRefundCount(array $alreadyRefunded, array $toRefund)
    {
        $actualRefund = [];
        foreach ($toRefund as $itemId => $qty) {

            if (isset($alreadyRefunded[$itemId])) {
                $actualRefund[$itemId] = $qty - $alreadyRefunded[$itemId];
            } else {
                $actualRefund[$itemId] = $qty;
            }

            if ($actualRefund[$itemId] == 0) {
                unset($actualRefund[$itemId]);
            }
        }
        return $actualRefund;
    }

    /**
     * @param \Mage_Sales_Model_Order $order
     * @return array
     */
    public function getItemsRefunded(\Mage_Sales_Model_Order $order)
    {
        $items = [];

        /** @var \Mage_Sales_Model_Order_Creditmemo $creditMemo */
        foreach ($order->getCreditmemosCollection() as $creditMemo) {
            /** @var \Mage_Sales_Model_Order_Creditmemo_Item $item */
            foreach ($creditMemo->getAllItems() as $item) {
                if (!isset($items[$item->getData('order_item_id')])) {
                    $items[$item->getData('order_item_id')] = $item->getQty();
                } else {
                    $items[$item->getData('order_item_id')] += $item->getQty();
                }
            }
        }

        return $items;
    }

    /**
     * @param \Mage_Sales_Model_Order $order
     * @param array $items
     * @return array
     * @throws WriterException
     */
    public function validateItemsToBeRefunded(\Mage_Sales_Model_Order $order, array $items)
    {
        $return = [];
        foreach ($items as $item) {
            $orderItem = $order->getItemsCollection()->getItemByColumnValue('sku', $item['sku']);
            if (null === $orderItem) {
                throw new WriterException('Item with SKU: "%s" does not exist in Order: "%s"', $item['sku']);
            }

            $return[$orderItem->getId()] = $item['qty'];
        }

        return $return;
    }


    /**
     * @param \Mage_Sales_Model_Order $order
     * @return \Mage_Sales_Model_Service_Order
     */
    public function getServiceForOrder(\Mage_Sales_Model_Order $order)
    {
        return \Mage::getModel('sales/service_order', $order);
    }
}
