<?php
/**
 * File containing the ezcGraphUnknownColorDefinitionException class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Exception thrown, when a given value could not be interpreted as a color by
 * {@link ezcGraphColor}.
 *
 * @package Graph
 * @version 1.5
 */
class ezcGraphUnknownColorDefinitionException extends ezcGraphException
{
    /**
     * Constructor
     * 
     * @param mixed $definition
     * @return void
     * @ignore
     */
    public function __construct( $definition )
    {
        parent::__construct( "Unknown color definition '{$definition}'." );
    }
}

?>
