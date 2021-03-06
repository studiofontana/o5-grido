<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Grido\DataSources;

/**
 * Dibi Fluent data source.
 *
 * @package     Grido
 * @subpackage  DataSources
 * @author      Petr Bugyík
 *
 * @property-read \DibiFluent $fluent
 * @property-read int $limit
 * @property-read int $offset
 * @property-read int $count
 * @property-read array $data
 */
class DibiFluent  implements IDataSource
{
    use Nette\SmartObject;

    /** @var \DibiFluent */
    protected $fluent;

    /** @var int */
    protected $limit;

    /** @var int */
    protected $offset;

    /**
     * @param \DibiFluent $fluent
     */
    public function __construct(\DibiFluent $fluent)
    {
        $this->fluent = $fluent;
    }

    /**
     * @return \DibiFluent
     */
    public function getFluent()
    {
        return $this->fluent;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param Grido\Components\Filters\Condition $condition
     * @param \DibiFluent $fluent
     */
    protected function makeWhere(\Grido\Components\Filters\Condition $condition, \DibiFluent $fluent = NULL)
    {
        $fluent = $fluent === NULL
            ? $this->fluent
            : $fluent;

        if ($condition->callback) {
            callback($condition->callback)->invokeArgs(array($condition->value, $fluent));
        } else {
            call_user_func_array(array($fluent, 'where'), $condition->__toArray('[', ']'));
        }
    }

    /*********************************** interface IDataSource ************************************/

    /**
     * @return int
     */
    public function getCount()
    {
        $fluent = clone $this->fluent;
        return $fluent->count();
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->fluent->fetchAll($this->offset, $this->limit);
    }

    /**
     * @param array $conditions
     */
    public function filter(array $conditions)
    {
        foreach ($conditions as $condition) {
            $this->makeWhere($condition);
        }
    }

    /**
     * @param int $offset
     * @param int $limit
     */
    public function limit($offset, $limit)
    {
        $this->offset = $offset;
        $this->limit = $limit;
    }

    /**
     * @param array $sorting
     */
    public function sort(array $sorting)
    {
        foreach ($sorting as $column => $sort) {
            $this->fluent->orderBy($column, $sort);
        }
    }

    /**
     * @param string $column
     * @param array $conditions
     * @return array
     */
    public function suggest($column, array $conditions)
    {
        if (!is_string($column)) {
            throw new \InvalidArgumentException('Suggest column must be string.');
        }

        $fluent = clone $this->fluent;
        foreach ($conditions as $condition) {
            $this->makeWhere($condition, $fluent);
        }

        $items = array();
        $data = $fluent->fetchPairs($column, $column);
        foreach ($data as $key => $value) {
            $value = (string) $value;
            $items[$value] = $value;
        }

        $items = array_values($items);
        sort($items);

        return $items;;
    }
}
