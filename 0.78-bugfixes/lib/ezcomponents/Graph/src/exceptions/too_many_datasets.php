<?php
/**
 * File containing the ezcGraphTooManyDataSetsExceptions class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Exception thrown when trying to insert too many data sets in a data set 
 * container.
 *
 * @package Graph
 * @version 1.5
 */
class ezcGraphTooManyDataSetsExceptions extends ezcGraphException
{
    /**
     * Constructor
     * 
     * @return void
     * @ignore
     */
    public function __construct()
    {
        parent::__construct( "You tried to insert to many datasets." );
    }
}

?>
