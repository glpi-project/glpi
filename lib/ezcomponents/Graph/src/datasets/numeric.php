<?php
/**
 * File containing the ezcGraphNumericDataSet class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Dataset for numeric data.
 *
 * Uses user defined functions for numeric data creation
 *
 * @property float $start
 *           Start value for x axis values of function
 * @property float $end
 *           End value for x axis values of function
 * @property callback $callback
 *           Callback function which represents the mathmatical function to 
 *           show
 * @property int $resolution
 *           Steps used to draw line in graph
 *
 * @version 1.5
 * @package Graph
 * @mainclass
 */
class ezcGraphNumericDataSet extends ezcGraphDataSet 
{
    /**
     * Position of the data iterator. Depends on the configured resolution.
     * 
     * @var int
     */
    protected $position = 0;

    /**
     * Container to hold the properties
     *
     * @var array(string=>mixed)
     */
    protected $properties;

    /**
     * Constructor
     * 
     * @param float $start Start value for x axis values of function
     * @param float $end End value for x axis values of function
     * @param callback $callback Callback function
     * @return void
     * @ignore
     */
    public function __construct( $start = null, $end = null, $callback = null )
    {
        parent::__construct();

        $this->properties['start'] = null;
        $this->properties['end'] = null;
        $this->properties['callback'] = null;

        if ( $start !== null )
        {
            $this->start = $start;
        }

        if ( $end !== null )
        {
            $this->end = $end;
        }

        if ( $callback !== null )
        {
            $this->callback = $callback;
        }

        $this->properties['resolution'] = 100;
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
     * @return mixed
     */
    public function __set( $propertyName, $propertyValue ) 
    {
        switch ( $propertyName ) {
            case 'resolution':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 1 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int > 1' );
                }

                $this->properties['resolution'] = (int) $propertyValue;
                break;
            case 'start':
            case 'end':
                if ( !is_numeric( $propertyValue ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'float' );
                }

                $this->properties[$propertyName] = (float) $propertyValue;
                break;
            case 'callback':
                if ( !is_callable( $propertyValue ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'callback' );
                }

                $this->properties[$propertyName] = $propertyValue;
                break;
            default:
                parent::__set( $propertyName, $propertyValue );
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
        return parent::__get( $propertyName );
    }

    /**
     * Get the x coordinate for the current position
     * 
     * @param int $position Position
     * @return float x coordinate
     */
    protected function getKey()
    {
        return $this->start +
            ( $this->end - $this->start ) / $this->resolution * $this->position;
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
        return ( ( $key >= $this->start ) && ( $key <= $this->end ) );
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
        return call_user_func( $this->callback, $key );
    }

    /**
     * Throws a ezcBasePropertyPermissionException because single datapoints
     * cannot be set in average datasets.
     * 
     * @param string $key The kex of a datapoint to set.
     * @param float $value The value for the datapoint.
     * @throws ezcBasePropertyPermissionException
     *         Always, because access is readonly.
     * @return void
     */
    public function offsetSet( $key, $value )
    {
        throw new ezcBasePropertyPermissionException( $key, ezcBasePropertyPermissionException::READ );
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
    final public function current()
    {
        return call_user_func( $this->callback, $this->getKey() );
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
    final public function next()
    {
        if ( $this->start === $this->end )
        {
            throw new ezcGraphDatasetAverageInvalidKeysException();
        }

        if ( ++$this->position >= $this->resolution )
        {
            return false;
        }
        else 
        {
            return $this->current();
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
    final public function key()
    {
        return (string) $this->getKey();
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
    final public function valid()
    {
        return ( ( $this->getKey() >= $this->start ) && ( $this->getKey() <= $this->end ) );
    }

    /**
     * Selects the very first datapoint and returns it.
     * This method is part of the Iterator interface to allow access to the 
     * datapoints of this row by iterating over it like an array (e.g. using
     * foreach).
     *
     * @return float The very first datapoint.
     */
    final public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Returns the number of elements in this dataset
     * 
     * @return int
     */
    public function count()
    {
        return $this->resolution + 1;
    }
}
?>
