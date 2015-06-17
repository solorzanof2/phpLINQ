<?php

/**
 *  LINQ concept for PHP
 *  Copyright (C) 2015  Marcel Joachim Kloubert <marcel.kloubert@gmx.net>
 *
 *    This library is free software; you can redistribute it and/or
 *    modify it under the terms of the GNU Lesser General Public
 *    License as published by the Free Software Foundation; either
 *    version 3.0 of the License, or (at your option) any later version.
 *
 *    This library is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 *    Lesser General Public License for more details.
 *
 *    You should have received a copy of the GNU Lesser General Public
 *    License along with this library.
 */


namespace System\Collections;


/**
 * A common collection / list.
 *
 * @author Marcel Joachim Kloubert <marcel.kloubert@gmx.net>
 * @package System\Collections
 */
final class Collection extends EnumerableBase implements IList {
    private $_equalityComparer;
    private $_items;


    /**
     * Initializes a new instance of that class.
     *
     * @param mixed $items The initial items.
     *                     If there is only one argument and the value is callable, it
     *                     is used as key comparer.
     * @param callable $equalityComparer The optional key comparer.
     */
    public function __construct($items = null, $equalityComparer = null) {
        $this->_equalityComparer = static::getEqualComparerSafe($equalityComparer);

        $this->clear();
        if (!is_null($items)) {
            $this->addRange($items);
        }

        $this->reset();
    }


    public function add($item) {
        $this->_items[] = $item;

        return $this->count() - 1;
    }

    public function addItems() {
        $this->addRange(func_get_args());
    }

    public function addRange($items) {
        foreach ($items as $i) {
            $this->_items[] = $i;
        }
    }

    public function clear() {
        $this->_items = array();
    }

    private function compareItems($x, $y) {
        return call_user_func($this->_equalityComparer,
                              $x, $y);
    }

    public function containsItem($item) {
        return $this->indexOf($item) > -1;
    }

    public final function count() {
        return count($this->_items);
    }

    public function elementAtOrDefault($index, $defValue = null) {
        if (isset($this->_items[$index])) {
            return $this->_items[$index];
        }

        return $defValue;
    }

    public function indexOf($item) {
        $index = -1;
        foreach ($this->_items as $i) {
            ++$index;

            if ($this->compareItems($item, $i)) {
                // found
                return $index;
            }
        }

        // not found
        return -1;
    }

    public function insert($index, $item) {
        if (!$this->offsetExists($index)) {
            $this->throwIndexOfOfRange($index);
        }

        $newItems = array();
        for ($i = 0; $i < count($this->_items); $i++) {
            if ($i == $index) {
                $newItems[] = $item;
            }

            $newItems[] = $this->_items[$i];
        }

        $this->_items = $newItems;
    }

    public function isFixedSize() {
        return $this->isReadOnly();
    }

    public function isReadOnly() {
        return false;
    }

    public function isSynchronized() {
        return false;
    }

    public function offsetExists($index) {
        return isset($this->_items[$index]);
    }

    public function offsetGet($index) {
        if ($this->offsetExists($index)) {
            return $this->_items[$index];
        }

        $this->throwIndexOfOfRange($index);
    }

    public function offsetSet($index, $value) {
        if (is_null($index)) {
            $this->add($value);
            return;
        }

        if ($this->offsetExists($index)) {
            $this->_items[$index] = $value;
            return;
        }

        $this->throwIndexOfOfRange($index);
    }

    public function offsetUnset($index) {
        $this->removeAt($index);
    }

    public function remove($item) {
        $index = $this->indexOf($item);
        if ($index > -1) {
            $this->removeAt($index);
            return true;
        }

        return false;
    }

    public function removeAt($index) {
        if ($this->offsetExists($index)) {
            array_splice($this->_items, $index, 1);
            return;
        }

        $this->throwIndexOfOfRange($index);
    }

    public function rewind() {
        $this->_i = new \ArrayIterator($this->_items);
    }

    private function throwIndexOfOfRange($index) {
        $this->throwException(sprintf("Index '%s' not found!",
                                      $index));
    }
}