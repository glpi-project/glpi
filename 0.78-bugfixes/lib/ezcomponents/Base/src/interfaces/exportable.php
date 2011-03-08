<?php
/**
 * File containing the ezcBaseExportable interface.
 *
 * @package Base
 * @version 1.8
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Interface for class of which instances can be exported using var_export().
 *
 * In some components, objects can be stored (e.g. to disc) using the var_export() 
 * function. To ensure that an object supports proper importing again, this 
 * interface should be implemented.
 *
 * @see var_export()
 */
interface ezcBaseExportable
{
    /**
     * Returns an instance of the desired object, initialized from $state.
     *
     * This method must return a new instance of the class it is implemented 
     * in, which has its properties set from the given $state array.
     *
     * @param array $state 
     * @return object
     */
    public static function __set_state( array $state );
}

?>
