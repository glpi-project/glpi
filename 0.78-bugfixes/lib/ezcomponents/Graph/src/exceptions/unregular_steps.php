<?php
/**
 * File containing the ezcGraphUnregularStepsException class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Exception thrown when a bar chart shouls be rendered on an axis using 
 * unregular step sizes.
 *
 * @package Graph
 * @version 1.5
 */
class ezcGraphUnregularStepsException extends ezcGraphException
{
    /**
     * Constructor
     * 
     * @return void
     * @ignore
     */
    public function __construct()
    {
        parent::__construct( "Bar charts do not support axis with unregular steps sizes." );
    }
}

?>
