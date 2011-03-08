<?php
/**
 * File containing the ezcBaseInitInvalidCallbackClassException class
 *
 * @package Configuration
 * @version 1.8
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Exception that is thrown if an invalid class is passed as callback class for
 * delayed object configuration.
 *
 * @package Configuration
 * @version 1.8
 */
class ezcBaseInitInvalidCallbackClassException extends ezcBaseException
{
    /**
     * Constructs a new ezcBaseInitInvalidCallbackClassException for the $callbackClass.
     *
     * @param string $callbackClass
     * @return void
     */
    function __construct( $callbackClass )
    {
        parent::__construct( "Class '{$callbackClass}' does not exist, or does not implement the 'ezcBaseConfigurationInitializer' interface." );
    }
}
?>
