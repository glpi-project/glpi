<?php
/**
 * File containing the ezcBaseInvalidParentClassException class
 *
 * @package Base
 * @version 1.8
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Exception that is thrown if an invalid class is passed as custom class.
 *
 * @package Base
 * @version 1.8
 */
class ezcBaseInvalidParentClassException extends ezcBaseException
{
    /**
     * Constructs an ezcBaseInvalidParentClassException for custom class $customClass
     *
     * @param string $expectedParentClass
     * @param string $customClass
     */
    function __construct( $expectedParentClass, $customClass )
    {
        parent::__construct( "Class '{$customClass}' does not exist, or does not inherit from the '{$expectedParentClass}' class." );
    }
}
?>
