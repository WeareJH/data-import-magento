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
     * @var \Mage_Sales_Model_Order
     */
    protected $orderModel;

    /**
     * @param \Mage_Sales_Model_Order $orderModel
     */
    public function __construct(
        \Mage_Sales_Model_Order $orderModel
    ) {
        $this->orderModel = $orderModel;
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

        } catch (\Exception $e) {
            throw new MagentoSaveException($e->getMessage());
        }
    }
}
