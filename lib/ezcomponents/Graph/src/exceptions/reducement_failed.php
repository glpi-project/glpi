<?php
/**
 * File containing the ezcGraphReducementFailedException class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Exception thrown when a requested reducement of an ellipse or polygone 
 * failed because the shape was already too small.
 *
 * @package Graph
 * @version 1.5
 */
class ezcGraphReducementFailedException extends ezcGraphException
{
    /**
     * Constructor
     * 
     * @return void
     * @ignore
     */
    public function __construct()
    {
        parent::__construct( "Reducement of shape failed, because it was already too small." );
    }
}

?>
