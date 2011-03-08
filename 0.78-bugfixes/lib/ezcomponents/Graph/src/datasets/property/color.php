<?php
/**
 * File containing the abstract ezcGraphDataSetColorProperty class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Class for color properties of datasets
 *
 * This class is used to store properties for datasets, which should be
 * validated as objects extending the ezcGraphColor class.
 *
 * For a basic usage example of those dataset properties take a look at the API
 * documentation of the ezcGraphDataSetProperty class.
 *
 * @version 1.5
 * @package Graph
 */
class ezcGraphDataSetColorProperty extends ezcGraphDataSetProperty
{
    /**
     * Converts value to an {@link ezcGraphColor} object
     * 
     * @param & $value 
     * @return void
     */
    protected function checkValue( &$value )
    {
        $value = ezcGraphColor::create( $value );
        return true;
    }
}

?>
