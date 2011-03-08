<?php
/**
 * File containing the ezcGraphInvalidDisplayTypeException class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Exception thrown when an unsupported data type is set for the current chart.
 *
 * @package Graph
 * @version 1.5
 */
class ezcGraphInvalidDisplayTypeException extends ezcGraphException
{
    /**
     * Constructor
     * 
     * @param int $type
     * @return void
     * @ignore
     */
    public function __construct( $type )
    {
        $chartTypeNames = array(
            ezcGraph::PIE => 'Pie',
            ezcGraph::LINE => 'Line',
            ezcGraph::BAR => 'Bar',
        );

        if ( isset( $chartTypeNames[$type] ) )
        {
            $chartTypeName = $chartTypeNames[$type];
        }
        else
        {
            $chartTypeName = 'Unknown';
        }

        parent::__construct( "Invalid data set display type '$type' ('$chartTypeName') for current chart." );
    }
}

?>
