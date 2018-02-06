<?php

/**
 *    Copyright 2015-2017 ppy Pty. Ltd.
 *
 *    This file is part of osu!web. osu!web is distributed with the hope of
 *    attracting more community contributions to the core ecosystem of osu!.
 *
 *    osu!web is free software: you can redistribute it and/or modify
 *    it under the terms of the Affero GNU General Public License version 3
 *    as published by the Free Software Foundation.
 *
 *    osu!web is distributed WITHOUT ANY WARRANTY; without even the implied
 *    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *    See the GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with osu!web.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Libraries\Elasticsearch;

class SearchResults implements \ArrayAccess, \Countable, \Iterator
{
    /**
     * @var string
     */
    private $container;

    /**
     * @var array
     */
    private $raw;

    private $index;

    public function __construct(array $results)
    {
        $this->raw = $results;

        $this->index = 0;
    }

    public function hits()
    {
        return $this->raw['hits']['hits'];
    }

    public function innerHits($index, string $name)
    {
        $results = $this->hits()[$index] ?? null;
        $results = $results['inner_hits'][$name];

        if ($results) {
            return new static($results, $name);
        }
    }

    public function raw()
    {
        return $this->raw;
    }

    /**
     * Returns an array of ids extracted from the search response.
     *
     * The _id field is the default field used for ids; a custom field can be
     * specified to be used as the id field instead. If a custom field is used,
     * the _source for that field must be included in the query.
     *
     * @param string $field The field to use as the id field.
     *
     * @return array
     */
    public function ids(string $field = '_id')
    {
        if ($field === '_id') {
            return array_map(function ($hit) use ($field) {
                return $hit[$field];
            }, $this->hits());
        } else {
            return array_map(function ($hit) use ($field) {
                return $hit['_source'][$field];
            }, $this->hits());
        }
    }

    public function total()
    {
        return $this->raw()['hits']['total'];
    }

    public function count()
    {
        return count($this->hits());
    }

    public function offsetExists($key)
    {
        return array_has($this->hits(), $key);
    }

    public function offsetGet($key)
    {
        return data_get($this->hits(), $key);
    }

    public function offsetSet($key, $value)
    {
        throw new \BadMethodCallException('not supported');
    }

    public function offsetUnset($key)
    {
        throw new \BadMethodCallException('not supported');
    }

    public function current()
    {
        return $this[$this->index];
    }

    public function key()
    {
        return $this->index;
    }

    public function next()
    {
        ++$this->index;
    }

    public function rewind()
    {
        $this->index = 0;
    }

    public function valid()
    {
        return $this->offsetExists($this->index);
    }
}
