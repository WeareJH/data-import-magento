<?php

namespace Jh\DataImportMagento\Reader;

use Ddeboer\DataImport\Exception\ReaderException;
use Ddeboer\DataImport\Reader\ReaderInterface;

/**
 * Class AbstractMagentoReader
 * @package Jh\DataImportMagento\Reader
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class MagentoReader implements ReaderInterface
{
    /**
     * @var \Mage_Core_Model_Resource_Db_Collection_Abstract
     */
    protected $collection;

    /**
     * @var Zend_Db_Statement_Interface
     */
    protected $statement;

    /**
     * @var array Current Row
     */
    protected $data = array();

    /**
     * @var int
     */
    protected $current = 0;

    /**
     * @var int|null
     */
    protected $count = null;

    /**
     * @var null|\Varien_Db_Select
     */
    protected $select = null;

    /**
     * @param \Mage_Core_Model_Resource_Db_Collection_Abstract $collection
     */
    public function __construct(\Mage_Core_Model_Resource_Db_Collection_Abstract $collection)
    {
        $this->collection = $collection;

        //get SQL statement
        $this->select = $this->collection->getSelect();
    }

    /**
     * Get the next row of data and store it
     *
     * @return array
     */
    public function current()
    {
        return $this->data;
    }

    /**
     * Fetch next row and
     * advance the current row index
     */
    public function next()
    {
        $this->data = $this->statement->fetch();
        $this->current++;
    }

    /**
     * Return current position
     *
     * @return int
     */
    public function key()
    {
        return $this->current;
    }

    /**
     * Check whether the current index is not greater
     * then the size of the collection
     *
     * @return bool
     */
    public function valid()
    {
        /**
         * Make sure $this->data is not false
         * Wierd bug with Magento getSize() on a collection
         * using joins & GROUP BY. COUNT(DISTINCT(idfield))
         * seems to fix it but is hard to patch. This simple check should return false
         * if the row if null
         */
        return $this->current <= $this->count() && $this->data;
    }

    /**
     * Rewind to the first element
     */
    public function rewind()
    {
        $this->statement = $this->select->query();
        $this->current = 0;
        $this->next();
    }

    /**
     * Get the field (column, property) names
     *
     * @return array
     */
    public function getFields()
    {
        if (empty($this->data)) {
            $data = $this->select->query()->fetch();
        }

        return array_keys($data);
    }

    /**
     * Magento creates a COUNT(*) query for us
     *
     * @return int
     */
    public function count()
    {
        if (null == $this->count) {
            $this->count = $this->collection->getSize();
        }
        return $this->count;
    }
}
