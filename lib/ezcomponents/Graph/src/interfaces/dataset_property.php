<?php
/**
 * File containing the abstract ezcGraphDataSetProperty class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Abstract class for properties of datasets
 *
 * This class is used to extends datasets with additional properties, and
 * stores only non default values for each data point in a data set.
 * 
 * The class is extended by property implementations including simple value
 * validators, like:
 *
 * - ezcGraphDataSetAxisProperty
 * - ezcGraphDataSetBooleanProperty
 * - ezcGraphDataSetColorProperty
 * - ezcGraphDataSetIntProperty
 * - ezcGraphDataSetStringProperty
 *
 * The color property can for example be accessed in a chart like:
 *
 * <code>
 *  $graph = new ezcGraphLineChart();
 *  $graph->data['example'] = new ezcGraphArrayDataSet( array(
 *      'Foo' => 23,
 *      'Bar' => 42,
 *  ) );
 *
 *  // Set color for all data points in this data set
 *  $graph->data['example']->color = '#a40000';
 *
 *  // Set different color for one special datapoint
 *  $graph->data['example']->color['Foo'] = '#2e3436';
 *
 *  $graph->render( 400, 200, 'test.svg' );
 * </code>
 *
 * @version 1.5
 * @package Graph
 */
abstract class ezcGraphDataSetProperty implements ArrayAccess
{
    /**
     * Default value for this property
     * 
     * @var mixed
     */
    protected $defaultValue;

    /**
     * Contains specified values for single dataset elements
     * 
     * @var array
     */
    protected $dataValue;

    /**
     * Contains a reference to the dataset to check for availability of data
     * keys
     * 
     * @var ezcGraphDataSet
     */
    protected $dataset;

    /**
     * Abstract method to contain the check for validity of the value
     * 
     * @param mixed $value 
     * @return void
     */
    abstract protected function checkValue( &$value );

    /**
     * Constructor
     * 
     * @param ezcGraphDataSet $dataset 
     * @ignore
     * @return void
     */
    public function __construct( ezcGraphDataSet $dataset )
    {
        $this->dataset = $dataset;
    }

    /**
     * Set the default value for this property
     * 
     * @param string $name Property name
     * @param mixed $value Property value
     * @return void
     */
    public function __set( $name, $value )
    {
        if ( $name === 'default' &&
             $this->checkValue( $value ) )
        {
            $this->defaultValue = $value;
        }
    }

    /**
     * Get the default value for this property
     * 
     * @param string $name Property name
     * @return mixed
     */
    public function __get( $name )
    {
        if ( $name === 'default' )
        {
            return $this->defaultValue;
        }
    }

    /**
     * Returns if an option exists.
     * Allows isset() using ArrayAccess.
     * 
     * @param string $key The name of the option to get.
     * @return bool Wether the option exists.
     */
    final public function offsetExists( $key )
    {
        return isset( $this->dataset[$key] );
    }

    /**
     * Returns an option value.
     * Get an option value by ArrayAccess.
     * 
     * @param string $key The name of the option to get.
     * @return mixed The option value.
     *
     * @throws ezcBasePropertyNotFoundException
     *         If a the value for the property options is not an instance of
     */
    final public function offsetGet( $key )
    {
        if ( isset( $this->dataValue[$key] ) )
        {
            return $this->dataValue[$key];
        }
        elseif ( isset( $this->dataset[$key] ) )
        {
            return $this->defaultValue;
        }
        else
        {
            throw new ezcGraphNoSuchDataException( $key );
        }
    }

    /**
     * Set an option.
     * Sets an option using ArrayAccess.
     * 
     * @param string $key The option to set.
     * @param mixed $value The value for the option.
     * @return void
     *
     * @throws ezcBasePropertyNotFoundException
     *         If a the value for the property options is not an instance of
     * @throws ezcBaseValueException
     *         If a the value for a property is out of range.
     */
    public function offsetSet( $key, $value )
    {
        if ( isset( $this->dataset[$key] ) &&
             $this->checkValue( $value ) )
        {
            $this->dataValue[$key] = $value;
        }
        else
        {
            throw new ezcGraphNoSuchDataException( $key );
        }
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
    final public function offsetUnset( $key )
    {
        if ( isset( $this->dataset[$key] ) )
        {
            unset( $this->dataValue[$key] );
        }
        else
        {
            throw new ezcGraphNoSuchDataException( $key );
        }
    }
}

?>
