<?php
/**
 * File containing the ezcGraphInvalidImageFileException class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Exception thrown when a file can not be used as a image file.
 *
 * @package Graph
 * @version 1.5
 */
class ezcGraphInvalidImageFileException extends ezcGraphException
{
    /**
     * Constructor
     * 
     * @param string $image
     * @return void
     * @ignore
     */
    public function __construct( $image )
    {
        parent::__construct( "File '{$image}' is not a valid image." );
    }
}

?>
