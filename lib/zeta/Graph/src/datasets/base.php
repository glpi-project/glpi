<?php
/**
 * File containing the abstract ezcGraphDataSet class
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 * 
 *   http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @package Graph
 * @version //autogentag//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @access private
 */
/**
 * Basic class to contain the charts data
 *
 * @property string $label
 *           Labels for datapoint and datapoint elements
 * @property ezcGraphColor $color
 *           Colors for datapoint elements
 * @property int $symbol
 *           Symbols for datapoint elements
 * @property string $highlightValue
 *           Displayed string if a data point is highlighted
 * @property bool $highlight
 *           Status if datapoint element is hilighted
 * @property int $displayType
 *           Display type of chart data
 * @property string $url
 *           URL associated with datapoint
 * @property ezcGraphChartElementAxis $xAxis
 *           Associate dataset with a different X axis then the default one
 * @property ezcGraphChartElementAxis $yAxis
 *           Associate dataset with a different Y axis then the default one
 *
 * @version //autogentag//
 * @package Graph
 * @mainclass
 */
abstract class ezcGraphDataSet implements ArrayAccess, Iterator, Countable
{

    /**
     * Property array
     * 
     * @var array
     */
    protected $properties;

    /**
     * Array which contains the data of the datapoint
     * 
     * @var array
     */
    protected $data;

    /**
     * Current datapoint element
     * needed for iteration over datapoint with ArrayAccess
     * 
     * @var mixed
     */
    protected $current;

    /**
     * Color palette used for datapoint colorization
     * 
     * @var ezcGraphPalette
     */
    protected $pallet;

    /**
     * Array keys
     * 
     * @var array
     */
    protected $keys;

    /**
     * Constructor
     * 
     * @return void
     * @ignore
     */
    public function __construct()
    {
        $this->properties['label'] = new ezcGraphDataSetStringProperty( $this );
        $this->properties['color'] = new ezcGraphDataSetColorProperty( $this );
        $this->properties['symbol'] = new ezcGraphDataSetIntProperty( $this );
        $this->properties['lineThickness'] = new ezcGraphDataSetIntProperty( $this );
        $this->properties['highlight'] = new ezcGraphDataSetBooleanProperty( $this );
        $this->properties['highlightValue'] = new ezcGraphDataSetStringProperty( $this );
        $this->properties['displayType'] = new ezcGraphDataSetIntProperty( $this );
        $this->properties['url'] = new ezcGraphDataSetStringProperty( $this );

        $this->properties['xAxis'] = new ezcGraphDataSetAxisProperty( $this );
        $this->properties['yAxis'] = new ezcGraphDataSetAxisProperty( $this );

        $this->properties['highlight']->default = false;
    }

    /**
     * Options write access
     * 
     * @throws ezcBasePropertyNotFoundException
     *          If Option could not be found
     * @throws ezcBaseValueException
     *          If value is out of range
     * @param mixed $propertyName   Option name
     * @param mixed $propertyValue  Option value;
     * @return void
     * @ignore
     */
    public function __set( $propertyName, $propertyValue )
    {
        switch ( $propertyName )
        {
            case 'hilight':
                $propertyName = 'highlight';
            case 'label':
            case 'url':
            case 'color':
            case 'symbol':
            case 'lineThickness':
            case 'highlight':
            case 'highlightValue':
            case 'displayType':
            case 'xAxis':
            case 'yAxis':
                $this->properties[$propertyName]->default = $propertyValue;
                break;

            case 'palette':
                $this->palette = $propertyValue;
                $this->color->default = $this->palette->dataSetColor;
                $this->symbol->default = $this->palette->dataSetSymbol;
                break;

            default:
                throw new ezcBasePropertyNotFoundException( $propertyName );
                break;
        }
    }

    /**
     * Property get access.
     * Simply returns a given option.
     * 
     * @param string $propertyName The name of the option to get.
     * @return mixed The option value.
     *
     * @throws ezcBasePropertyNotFoundException
     *         If a the value for the property options is not an instance of
     */
    public function __get( $propertyName )
    {
        if ( array_key_exists( $propertyName, $this->properties ) )
        {
            return $this->properties[$propertyName];
        }
        else 
        {
            throw new ezcBasePropertyNotFoundException( $propertyName );
        }
    }
    
    /**
     * Returns true if the given datapoint exists
     * Allows isset() using ArrayAccess.
     * 
     * @param string $key The key of the datapoint to get.
     * @return bool Wether the key exists.
     */
    public function offsetExists( $key )
    {
        return isset( $this->data[$key] );
    }

    /**
     * Returns the value for the given datapoint
     * Get an datapoint value by ArrayAccess.
     * 
     * @param string $key The key of the datapoint to get.
     * @return float The datapoint value.
     */
    public function offsetGet( $key )
    {
        return $this->data[$key];
    }

    /**
     * Sets the value for a datapoint.
     * Sets an datapoint using ArrayAccess.
     * 
     * @param string $key The kex of a datapoint to set.
     * @param float $value The value for the datapoint.
     * @return void
     */
    public function offsetSet( $key, $value )
    {
        $this->data[$key] = (float) $value;
    }

    /**
     * Unset an option.
     * Unsets an option using ArrayAccess.
     * 
     * @param string $key The options to unset.
     * @return void
     *
     * @throws ezcBasePropertyNotFoundException
     *         If a the value for the property options is not an instance of
     * @throws ezcBaseValueException
     *         If a the value for a property is out of range.
     */
    public function offsetUnset( $key )
    {
        unset( $this->data[$key] );
    }

    /**
     * Returns the currently selected datapoint.
     *
     * This method is part of the Iterator interface to allow access to the 
     * datapoints of this row by iterating over it like an array (e.g. using
     * foreach).
     * 
     * @return string The currently selected datapoint.
     */
    public function current()
    {
        if ( !isset( $this->current ) )
        {
            $this->keys    = array_keys( $this->data );
            $this->current = 0;
        }

        return $this->data[$this->keys[$this->current]];
    }

    /**
     * Returns the next datapoint and selects it or false on the last datapoint.
     *
     * This method is part of the Iterator interface to allow access to the 
     * datapoints of this row by iterating over it like an array (e.g. using
     * foreach).
     *
     * @return float datapoint if it exists, or false.
     */
    public function next()
    {
        if ( ++$this->current >= count( $this->keys ) )
        {
            return false;
        }
        else 
        {
            return $this->data[$this->keys[$this->current]];
        }
    }

    /**
     * Returns the key of the currently selected datapoint.
     *
     * This method is part of the Iterator interface to allow access to the 
     * datapoints of this row by iterating over it like an array (e.g. using
     * foreach).
     * 
     * @return string The key of the currently selected datapoint.
     */
    public function key()
    {
        return $this->keys[$this->current];
    }

    /**
     * Returns if the current datapoint is valid.
     *
     * This method is part of the Iterator interface to allow access to the 
     * datapoints of this row by iterating over it like an array (e.g. using
     * foreach).
     *
     * @return bool If the current datapoint is valid
     */
    public function valid()
    {
        return isset( $this->keys[$this->current] );
    }

    /**
     * Selects the very first datapoint and returns it.
     * This method is part of the Iterator interface to allow access to the 
     * datapoints of this row by iterating over it like an array (e.g. using
     * foreach).
     *
     * @return float The very first datapoint.
     */
    public function rewind()
    {
        $this->keys    = array_keys( $this->data );
        $this->current = 0;
    }
}

?>
