<?php

namespace Jh\DataImportMagento\Writer;

use Ddeboer\DataImport\Exception\WriterException;
use Ddeboer\DataImport\Writer\WriterInterface;

/**
 * Class OrderStatusWriter
 * @author Aydin Hassan <aydin@hotmail.co.uk.com>
 */
class OrderStatusWriter implements WriterInterface
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
        'order_id_field'            => 'increment_id',
        'send_shipment_email'       => true,
        'send_credit_memo_email'    => true,
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
     * @return $this
     * @throws MagentoSaveException
     * @throws \Ddeboer\DataImport\Exception\WriterException
     */
    public function writeItem(array $item)
    {

        if (!isset($item['orderId'])) {
            throw new WriterException('order_id must be set');
        }

        $order = $this->getOrder($item['orderId']);

        $quantities             = $this->validateItemsToBeShipped($order, $item['items']);
        $alreadyRefunded        = $this->getItemsShipped($order, $quantities);
        //TODO: Make this configurable - Some Returns will not include the total qty's shipped
        //TODO: Just the exact qty to ship.
        $shipmentQuantities     = $this->getActualShipmentCount($alreadyRefunded, $quantities);

        if (count($shipmentQuantities)) {
            //ship it
            //TODO: Check if successful by catching exceptions
            try {
                $this->ship($order, $shipmentQuantities);
            } catch (\Exception $e) {
                //shipment failed - exit now? or carry on
            }
        }

        $quantities         = $this->validateItemsToBeRefunded($order, $item['items']);
        $alreadyReturned    = $this->getItemsRefunded($order, $quantities);
        //TODO: Make this configurable - Some Returns will not include the total qty's returned
        //TODO: Just the exact qty to return.
        $returnQuantities    = $this->getActualRefundCount($alreadyReturned, $quantities);

        if (count($returnQuantities)) {
            //credit memo it
            try {
                $this->creditMemo($order, $returnQuantities);
            } catch (\Exception $e) {
                //credit memo failed - exit now? or carry on
            }
        }

        if (isset($item['status']) && null !== $item['status']) {
            //Update Status
            $order->setStatus(strtolower($item['status']));
        }

        try {
            $order->save();
        } catch (\Exception $e) {
            throw new MagentoSaveException($e->getMessage());
        }

        return $this;
    }

    /**
     * Create a Credit Memo with the Specified Quantities
     *
     * @param \Mage_Sales_Model_Order $order
     * @param array $quantities
     */
    public function ship(\Mage_Sales_Model_Order $order, array $quantities)
    {

        if (!$order->canShip()) {
            //throw
        }

        $shipment = $order->prepareShipment($quantities);

        if ($this->options['send_shipment_email']) {
            $shipment->setEmailSent(true);
            $shipment->getOrder()->setCustomerNoteNotify(true);
        }

        $shipment->register();

        $shipment->getOrder()->setIsInProcess(true);

        $transactionSave = clone $this->transactionResourceModel;
        $transactionSave
            ->addObject($shipment)
            ->addObject($shipment->getOrder())
            ->save();

        if ($this->options['send_shipment_email']) {
            $shipment->sendEmail(true);
        }
    }

    /**
     * Create a Credit Memo with the Specified Quantities
     *
     * @param \Mage_Sales_Model_Order $order
     * @param array $quantities
     */
    public function creditMemo(\Mage_Sales_Model_Order $order, array $quantities)
    {

        if (!$order->canCreditmemo()) {
            //throw
        }

        $service = $this->getServiceForOrder($order);

        /** @var \Mage_Sales_Model_Order_Creditmemo $creditMemo */
        $creditMemo = $service->prepareCreditmemo([
            'qtys'              => $quantities,
            //TODO: Make this configurable - have an option whether to refund shipping or not
            //TODO: if yes, then grab the amount from the input data
            'shipping_amount'   => 0,
        ]);

        if ($this->options['send_credit_memo_email']) {
            $creditMemo->setEmailSent(true);
            $creditMemo->getOrder()->setCustomerNoteNotify(true);
        }

        //don't actually perform refund.
        //TODO: Make this configurable ^
        $creditMemo->addData([
            'offline_requested' => true,
        ]);

        $creditMemo->register();

        $transactionSave = clone $this->transactionResourceModel;
        $transactionSave
            ->addObject($creditMemo)
            ->addObject($creditMemo->getOrder())
            ->save();

        if ($this->options['send_credit_memo_email']) {
            $creditMemo->sendEmail(true);
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
     * @param array $alreadyShipped
     * @param array $toShip
     * @return array
     */
    public function getActualShipmentCount(array $alreadyShipped, array $toShip)
    {
        $actualShip = [];
        foreach ($toShip as $itemId => $qty) {

            if (isset($alreadyShipped[$itemId])) {
                $actualShip[$itemId] = $qty - $alreadyShipped[$itemId];
            } else {
                $actualShip[$itemId] = $qty;
            }

            if ($actualShip[$itemId] == 0) {
                unset($actualShip[$itemId]);
            }
        }
        return $actualShip;
    }

    /**
     * @param \Mage_Sales_Model_Order $order
     * @return array
     */
    public function getItemsShipped(\Mage_Sales_Model_Order $order)
    {
        $items = [];

        /** @var \Mage_Sales_Model_Order_Shipment $shipment */
        foreach ($order->getShipmentsCollection() as $shipment) {
            /** @var \Mage_Sales_Model_Order_Shipment_Item $item */
            foreach ($shipment->getAllItems() as $item) {
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
    public function validateItemsToBeShipped(\Mage_Sales_Model_Order $order, array $items)
    {
        $return = [];
        foreach ($items as $item) {
            $orderItem = $order->getItemsCollection()->getItemByColumnValue('sku', $item['sku']);
            if (null === $orderItem) {
                throw new WriterException(
                    sprintf('Item with SKU: "%s" does not exist in Order: "%s"', $item['sku'], $order->getIncrementId())
                );
            }

            $return[$orderItem->getId()] = $item['qtyShipped'];
        }

        return $return;
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
                throw new WriterException(
                    sprintf('Item with SKU: "%s" does not exist in Order: "%s"', $item['sku'], $order->getIncrementId())
                );
            }

            $return[$orderItem->getId()] = $item['qtyCancelled'];
        }

        return $return;
    }

    /**
     * Wrap up the writer after all items have been written
     *
     * @return WriterInterface
     */
    public function finish()
    {
    }

    /**
     * Prepare the writer before writing the items
     *
     * @return WriterInterface
     */
    public function prepare()
    {
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
