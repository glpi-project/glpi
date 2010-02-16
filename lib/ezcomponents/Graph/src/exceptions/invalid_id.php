<?php
/**
 * File containing the ezcGraphSvgDriverInvalidIdException class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Exception thrown when a id could not be found in a SVG document to insert 
 * elements in.
 *
 * @package Graph
 * @version 1.5
 */
class ezcGraphSvgDriverInvalidIdException extends ezcGraphException
{
    /**
     * Constructor
     *
     * @param string $id
     * @return void
     * @ignore
     */
    public function __construct( $id )
    {
        parent::__construct( "Could not find element with id '{$id}' in SVG document." );
    }
}

?>
