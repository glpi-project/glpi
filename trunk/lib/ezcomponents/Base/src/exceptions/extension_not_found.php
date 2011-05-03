<?php
/**
 * File containing the ezcBaseExtensionNotFoundException class
 *
 * @package Base
 * @version 1.8
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * ezcBaseExtensionNotFoundException is thrown when a requested PHP extension was not found.
 *
 * @package Base
 * @version 1.8
 */
class ezcBaseExtensionNotFoundException extends ezcBaseException
{
    /**
     * Constructs a new ezcBaseExtensionNotFoundException.
     *
     * @param string $name The name of the extension
     * @param string $version The version of the extension
     * @param string $message Additional text
     */
    function __construct( $name, $version = null, $message = null )
    {
        if ( $version === null )
        {
            parent::__construct( "The extension '{$name}' could not be found. {$message}" );
        }
        else
        {
            parent::__construct( "The extension '{$name}' with version '{$version}' could not be found. {$message}" );
        }
    }
}
?>
