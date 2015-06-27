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


namespace System\Linq;


/**
 * A sequence.
 *
 * @package System\Linq
 * @author Marcel Joachim Kloubert <marcel.kloubert@gmx.net>
 */
final class Enumerable extends \System\Collections\EnumerableBase {
    /**
     * Builds a new sequence by using a factory function.
     *
     * @param int $count The number of items to build.
     * @param callable $itemFactory The function that builds an item.
     *
     * @return static The new sequence.
     */
    public static function build($count, $itemFactory) {
        $items = array();

        $index   = 0;
        $prevVal = null;
        $tag     = null;
        while ($index < $count) {
            $ctx          = new \stdClass();
            $ctx->addItem = true;
            $ctx->cancel  = false;
            $ctx->count   = $count;
            $ctx->index   = $index;
            $ctx->isFirst = 0 == $index;
            $ctx->isLast  = ($index + 1) >= $count;
            $ctx->items   = &$items;
            $ctx->newKey  = null;
            $ctx->nextVal = null;
            $ctx->prevVal = $prevVal;
            $ctx->tag     = $tag;

            $newItem = call_user_func($itemFactory,
                                      $index, $ctx);

            $index++;

            if ($ctx->cancel) {
                break;
            }

            if ($ctx->addItem) {
                if (is_null($ctx->newKey)) {
                    // auto key
                    $items[] = $newItem;
                }
                else {
                    $items[$ctx->newKey] = $newItem;
                }
            }

            $count   = $ctx->count;
            $prevVal = $ctx->nextVal;
            $tag     = $ctx->tag;
        }

        return static::create($items);
    }

    /**
     * Builds a sequence with a specific number of random values.
     *
     * @param int $count The number of items to create.
     * @param int|callable $maxOrSeeder The exclusive maximum value (mt_getrandmax() - 1 by default).
     *                                  If there are only two arguments and that value is a callable
     *                                  it is set to (null) and its origin value to written to $seeder.
     * @param int $min The inclusive minimum value (0 by default).
     * @param callable $seeder The optional function that initializes the random
     *                         number generator.
     *
     * @return static The new instance.
     */
    public static function buildRandom($count, $maxOrSeeder = null, $min = null, $seeder = null) {
        if (2 == func_num_args()) {
            if (is_callable($maxOrSeeder)) {
                $seeder      = $maxOrSeeder;
                $maxOrSeeder = null;
            }
        }

        if (is_null($min)) {
            $min = 0;
        }

        if (is_null($maxOrSeeder)) {
            $maxOrSeeder =  mt_getrandmax();
        }

        if (!is_null($seeder)) {
            call_user_func($seeder);
        }

        return static::build($count,
                             function () use ($min, $maxOrSeeder) {
                                 return mt_rand($min, $maxOrSeeder - 1);
                             });
    }

    /**
     * Builds a sequence of items while a function returns (true).
     *
     * @param callable $itemFactoryPredicate The function that is used to build the items.
     *
     * @return static the new instance.
     */
    public static function buildWhile($itemFactoryPredicate) {
        $items = array();

        $index   = 0;
        $prevVal = null;
        $tag     = null;
        do
        {
            $ctx          = new \stdClass();
            $ctx->addItem = true;
            $ctx->cancel  = true;
            $ctx->index   = $index++;
            $ctx->isFirst = 0 == $index;
            $ctx->items   = &$items;
            $ctx->newKey  = null;
            $ctx->nextVal = null;
            $ctx->prevVal = $prevVal;
            $ctx->tag     = $tag;

            $newItem = call_user_func($itemFactoryPredicate, $ctx);
            if ($ctx->cancel) {
                // do not continue
                break;
            }

            if ($ctx->addItem) {
                if (is_null($ctx->newKey)) {
                    // auto key
                    $items[] = $newItem;
                }
                else {
                    $items[$ctx->newKey] = $newItem;
                }
            }

            $prevVal = $ctx->nextVal;
            $tag     = $ctx->tag;
        }
        while(true);

        return static::create($items);
    }

    /**
     * Creates a new instance.
     *
     * @param mixed $items The initial items.
     *
     * @return Enumerable The new instance.
     */
    public static function create($items = null) {
        if (is_null($items)) {
            $items = new \EmptyIterator();
        }

        return new static(static::asIterator($items));
    }

    public static function createEnumerable($items = null) {
        if (is_null($items)) {
            $items = new \EmptyIterator();
        }

        return new self(static::asIterator($items));
    }

    /**
     * Creates a new instance from JSON data.
     *
     * @param string $json The JSON data.
     *
     * @return Enumerable The new instance.
     */
    public static function fromJson($json) {
        return static::create(json_decode($json, true));
    }

    /**
     * Creates a new instance from a list of values.
     *
     * @param mixed $value... The initial values.
     *
     * @return Enumerable The new instance.
     */
    public static function fromValues() {
        return static::create(func_get_args());
    }

    /**
     * Creates a sequence with a range of numbers.
     *
     * @param number $start The start value.
     * @param number $count The number of items.
     * @param int|callable $increaseBy The increase value or the function that provides that value.
     *
     * @return static The new sequence.
     */
    public static function range($start, $count, $increaseBy = 1) {
        $increaseFunc = $increaseBy;
        if (!is_callable($increaseFunc)) {
            $increaseFunc = function() use ($increaseBy) {
                return $increaseBy;
            };
        }

        return static::build($count,
                             function($index, $ctx) use (&$start, $increaseFunc) {
                                 $result = $start;

                                 $start += call_user_func($increaseFunc,
                                                          $result, $ctx);

                                 return $result;
                             });
    }
}
