<?php
/**
 * File containing the abstract ezcGraphDataSetIntProperty class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Class for axis properties of datasets
 *
 * This class is used to store properties for datasets, which should be
 * validated as objects extending the ezcGraphChartElementAxis class.
 *
 * For a basic usage example of those dataset properties take a look at the API
 * documentation of the ezcGraphDataSetProperty class.
 *
 * @version 1.5
 * @package Graph
 */
class ezcGraphDataSetAxisProperty extends ezcGraphDataSetProperty
{
    /**
     * Chacks if value is really an axis
     * 
     * @param ezcGraphChartElementAxis $value 
     * @return void
     */
    protected function checkValue( &$value )
    {
       if ( ! $value instanceof ezcGraphChartElementAxis )
       {
           throw new ezcBaseValueException( 'default', $value, 'ezcGraphChartElementAxis' );
       }

       return true;
    }

    /**
     * Set an option.
     *
     * Sets an option using ArrayAccess.
     *
     * This is deaktivated, because you need not set a different axis for some
     * data point.
     * 
     * @param string $key The option to set.
     * @param mixed $value The value for the option.
     * @return void
     *
     * @throws ezcGraphInvalidAssignementException
     *         Always
     */
    public function offsetSet( $key, $value )
    {
        throw new ezcGraphInvalidAssignementException();
    }

}

?>
