<?php
/**
 * File containing the ezcGraphInvalidStepSizeException class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Exception thrown when the major or minor step size does not divide cleanly
 * the value span it should be used for.
 *
 * @package Graph
 * @version 1.5
 */
class ezcGraphInvalidStepSizeException extends ezcGraphException
{
    /**
     * Constructor
     * 
     * @param string $message
     * @return void
     * @ignore
     */
    public function __construct( $message )
    {
        parent::__construct( "Invalid step size on numeric axis: {$message}." );
    }
}

?>
