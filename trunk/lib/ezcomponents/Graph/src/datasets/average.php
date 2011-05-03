<?php
/**
 * File containing the ezcGraphDataSetAverage class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Extension of basic dataset to represent averation.
 * Algorithm: http://en.wikipedia.org/wiki/Least_squares
 *
 * @property int $polynomOrder
 *           Maximum order of polygon to interpolate from points
 * @property int $resolution
 *           Resolution used to draw line in graph
 *
 * @version 1.5
 * @package Graph
 * @mainclass
 */
class ezcGraphDataSetAveragePolynom extends ezcGraphDataSet 
{

    /**
     * Source dataset to base averation on.
     * 
     * @var ezcGraphDataSet
     */
    protected $source;

    /**
     * Calculated averation polynom
     * 
     * @var ezcGraphPolynom
     */
    protected $polynom = false;

    /**
     * Minimum key
     * 
     * @var float
     */
    protected $min = false;

    /**
     * Maximum key
     * 
     * @var float
     */
    protected $max = false;

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
     * @param ezcGraphDataSet $dataset Dataset to interpolate
     * @param int $order Maximum order of interpolating polynom
     * @return void
     * @ignore
     */
    public function __construct( ezcGraphDataSet $dataset, $order = 3 )
    {
        parent::__construct();

        $this->properties['resolution'] = 100;
        $this->properties['polynomOrder'] = (int) $order;

        $this->source = $dataset;
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
            case 'polynomOrder':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int > 0' );
                }

                $this->properties['polynomOrder'] = (int) $propertyValue;
                $this->polynom = false;
                break;
            case 'resolution':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 1 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'int > 1' );
                }

                $this->properties['resolution'] = (int) $propertyValue;
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
     * Build the polynom based on the given points.
     * 
     * @return void
     */
    protected function buildPolynom()
    {
        $points = array();

        foreach ( $this->source as $key => $value )
        {
            if ( !is_numeric( $key ) )
            {
                throw new ezcGraphDatasetAverageInvalidKeysException();
            }

            if ( ( $this->min === false ) || ( $this->min > $key ) )
            {
                $this->min = (float) $key;
            }

            if ( ( $this->max === false ) || ( $this->max < $key ) )
            {
                $this->max = (float) $key;
            }

            $points[] = new ezcGraphCoordinate( (float) $key, (float) $value );
        }

        // Build transposed and normal Matrix out of coordiantes
        $a = new ezcGraphMatrix( count( $points ), $this->polynomOrder + 1 );
        $b = new ezcGraphMatrix( count( $points ), 1 );

        for ( $i = 0; $i <= $this->properties['polynomOrder']; ++$i )
        {
            foreach ( $points as $nr => $point )
            {
                $a->set( $nr, $i, pow( $point->x, $i ) );
                $b->set( $nr, 0, $point->y );
            }
        }

        $at = clone $a;
        $at->transpose();
        
        $left = $at->multiply( $a );
        $right = $at->multiply( $b );

        $this->polynom = $left->solveNonlinearEquatation( $right );
    }

    /**
     * Returns a polynom of the defined order witch matches the datapoints
     * using the least squares algorithm.
     * 
     * @return ezcGraphPolynom Polynom
     */
    public function getPolynom()
    {
        if ( $this->polynom === false )
        {
            $this->buildPolynom();
        }

        return $this->polynom;
    }

    /**
     * Get the x coordinate for the current position
     * 
     * @param int $position Position
     * @return float x coordinate
     */
    protected function getKey()
    {
        $polynom = $this->getPolynom();
        return $this->min +
            ( $this->max - $this->min ) / $this->resolution * $this->position;
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
        $polynom = $this->getPolynom();
        return ( ( $key >= $this->min ) && ( $key <= $this->max ) );
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
        $polynom = $this->getPolynom();
        return $polynom->evaluate( $key );
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
        $polynom = $this->getPolynom();
        return $polynom->evaluate( $this->getKey() );
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
        $polynom = $this->getPolynom();

        if ( $this->min >= $this->max )
        {
            return false;
        }

        return ( ( $this->getKey() >= $this->min ) && ( $this->getKey() <= $this->max ) );
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
        return $this->resolution;
    }
}
?>
