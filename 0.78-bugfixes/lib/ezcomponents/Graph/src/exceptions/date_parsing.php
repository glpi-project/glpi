<?php
/**
 * File containing the ezcGraphErrorParsingDateException class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Exception thrown when a date assigned to the 
 * {@link ezcGraphChartElementDateAxis} could not be parsed.
 *
 * @package Graph
 * @version 1.5
 */
class ezcGraphErrorParsingDateException extends ezcGraphException
{
    /**
     * Constructor
     * 
     * @param mixed $value
     * @return void
     * @ignore
     */
    public function __construct( $value )
    {
        $type = gettype( $value );
        parent::__construct( "Could not parse date '{$value}' of type '{$type}'." );
    }
}

?>
