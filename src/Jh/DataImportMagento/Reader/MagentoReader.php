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
     * @param \Mage_Core_Model_Resource_Db_Collection_Abstract $collection
     */
    public function __construct(\Mage_Core_Model_Resource_Db_Collection_Abstract $collection)
    {
        $this->collection = $collection;

        //get SQL statement
        $select             = $this->collection->getSelect();
        $this->statement    = $this->getStatement($select);
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
        return ($this->count > $this->current);
    }

    /**
     * Rewind to the first element
     */
    public function rewind()
    {
        $this->statement = $this->getStatement($this->collection->getSelect());
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
            $this->current();
        }

        return array_keys($this->data);
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

    /**
     * @param $query
     * @return Zend_Db_Statement_Interface
     * @throws ReaderException
     */
    protected function getStatement($query)
    {
        if ($query instanceof \Zend_Db_Statement_Interface) {
            return $query;
        }

        if ($query instanceof \Zend_Db_Select) {
            return $query->query();
        }

        throw new ReaderException("Invalid Query Given");
    }
}
