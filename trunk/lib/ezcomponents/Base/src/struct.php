<?php
/**
 * File containing the ezcBaseStruct.
 *
 * @package Base
 * @version 1.8
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Base class for all struct classes.
 *
 * @package Base
 * @version 1.8
 */
class ezcBaseStruct
{
    /**
     * Throws a BasePropertyNotFound exception.
     *
     * @param string $name
     * @param mixed $value
     * @ignore
     */
    final public function __set( $name, $value )
    {
        throw new ezcBasePropertyNotFoundException( $name );
    }

    /**
     * Throws a BasePropertyNotFound exception.
     *
     * @param string $name
     * @ignore
     */
    final public function __get( $name )
    {
        throw new ezcBasePropertyNotFoundException( $name );
    }
}
?>
