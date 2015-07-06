<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Stdlib;

use Countable;
use Iterator;

/**
 * Priority list
 */
class PriorityList implements Iterator, Countable
{
    const EXTR_DATA     = 0x00000001;
    const EXTR_PRIORITY = 0x00000002;
    const EXTR_BOTH     = 0x00000003;
    /**
     * Internal list of all items.
     *
     * @var array
     */
    protected $items = array();

    /**
     * Serial assigned to items to preserve LIFO.
     *
     * @var int
     */
    protected $serial = 0;

    /**
     * Serial order mode
     * @var integer
     */
    protected $isLIFO = 1;

    /**
     * Internal counter to avoid usage of count().
     *
     * @var int
     */
    protected $count = 0;

    /**
     * Whether the list was already sorted.
     *
     * @var bool
     */
    protected $sorted = false;

    /**
     * Insert a new item.
     *
     * @param  string  $name
     * @param  mixed $value
     * @param  int $priority
     * @return void
     */
    public function insert($name, $value, $priority = 0)
    {
        $this->sorted = false;
        $this->count++;

        $this->items[$name] = array(
            'data'     => $value,
            'priority' => (int) $priority,
            'serial'   => $this->serial++,
        );
    }

    public function setPriority($name, $priority)
    {
        if (!isset($this->items[$name])) {
            throw new \Exception("item $name not found");
        }
        $this->items[$name]['priority'] = (int) $priority;
        $this->sorted = false;
        return $this;
    }

    /**
     * Remove a item.
     *
     * @param  string $name
     * @return void
     */
    public function remove($name)
    {
        if (!isset($this->items[$name])) {
            return;
        }

        $this->count--;
        unset($this->items[$name]);
    }

    /**
     * Remove all items.
     *
     * @return void
     */
    public function clear()
    {
        $this->items = array();
        $this->serial = 0;
        $this->count  = 0;
        $this->sorted = false;
    }

    /**
     * Get a item.
     *
     * @param  string $name
     * @return mixed
     */
    public function get($name)
    {
        if (!isset($this->items[$name])) {
            return null;
        }

        return $this->items[$name]['data'];
    }

    /**
     * Sort all items.
     *
     * @return void
     */
    protected function sort()
    {
        if (!$this->sorted) {
            uasort($this->items, array($this, 'compare'));
            $this->sorted = true;
        }
    }

    /**
     * Compare the priority of two items.
     *
     * @param  array $item1,
     * @param  array $item2
     * @return int
     */
    protected function compare(array $item1, array $item2)
    {
        return ($item1['priority'] === $item2['priority'])
            ? ($item1['serial']   > $item2['serial']   ? -1 : 1) * $this->isLIFO
            : ($item1['priority'] > $item2['priority'] ? -1 : 1);
    }

    /**
     * Get/Set serial order mode
     *
     * @param bool $flag
     * @return bool
     */
    public function isLIFO($flag = null)
    {
        if ($flag !== null) {
            if (($flag = ($flag === true ? 1 : -1)) !== $this->isLIFO) {
                $this->isLIFO = $flag;
                $this->sorted = false;
            }
        }
        return $this->isLIFO === 1;
    }

    /**
     * rewind(): defined by Iterator interface.
     *
     * @see    Iterator::rewind()
     * @return void
     */
    public function rewind()
    {
        $this->sort();
        reset($this->items);
    }

    /**
     * current(): defined by Iterator interface.
     *
     * @see    Iterator::current()
     * @return mixed
     */
    public function current()
    {
        $node = current($this->items);
        return ($node !== false ? $node['data'] : false);
    }

    /**
     * key(): defined by Iterator interface.
     *
     * @see    Iterator::key()
     * @return string
     */
    public function key()
    {
        return key($this->items);
    }

    /**
     * next(): defined by Iterator interface.
     *
     * @see    Iterator::next()
     * @return mixed
     */
    public function next()
    {
        $node = next($this->items);
        return ($node !== false ? $node['data'] : false);
    }

    /**
     * valid(): defined by Iterator interface.
     *
     * @see    Iterator::valid()
     * @return bool
     */
    public function valid()
    {
        return ($this->current() !== false);
    }

    /**
     * count(): defined by Countable interface.
     *
     * @see    Countable::count()
     * @return int
     */
    public function count()
    {
        return $this->count;
    }

    /**
     * Return list as array
     *
     * @param type $raw
     * @return array
     */
    public function toArray($flag = self::EXTR_DATA)
    {
        $this->sort();
        if ($flag == self::EXTR_BOTH) {
            return $this->items;
        }
        return array_map(
            ($flag == self::EXTR_PRIORITY)
                ? function ($item) { return $item['priority']; }
                : function ($item) { return $item['data']; },
            $this->items
        );
    }
}
