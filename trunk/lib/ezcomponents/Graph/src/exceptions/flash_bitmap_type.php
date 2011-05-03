<?php
/**
 * File containing the ezcGraphFlashBitmapTypeException class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Flash can only embed JPEGs and PNGs. This exception is thrown for * all
 * other image types.
 *
 * @package Graph
 * @version 1.5
 */
class ezcGraphFlashBitmapTypeException extends ezcGraphException
{
    /**
     * Constructor
     * 
     * @return void
     * @ignore
     */
    public function __construct()
    {
        parent::__construct( "Flash can only read JPEGs and PNGs." );
    }
}

?>
