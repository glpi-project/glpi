<?php
/**
 * File containing the ezcBaseAutoloadOptions class
 *
 * @package Base
 * @version 1.8
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Class containing the basic options for ezcBase' autoload.
 *
 * @property bool $preload
 *           If component preloading is enabled then as soon as one of the
 *           classes of a component is request, all other classes in the
 *           component are loaded as well (except for Exception classes).
 * @property bool $debug
 *           If debug is enabled then the autoload method will show exceptions
 *           when a class can not be found. Because exceptions are ignored by
 *           PHP in the autoload handler, you have to catch them in autoload()
 *           yourself and do something with the exception message.
 *
 * @package Base
 * @version 1.8
 */
class ezcBaseAutoloadOptions extends ezcBaseOptions
{
    /**
     * Constructs an object with the specified values.
     *
     * @throws ezcBasePropertyNotFoundException
     *         if $options contains a property not defined
     * @throws ezcBaseValueException
     *         if $options contains a property with a value not allowed
     * @param array(string=>mixed) $options
     */
    public function __construct( array $options = array() )
    {
        $this->preload = false;
        $this->debug = false;

        parent::__construct( $options );
    }

    /**
     * Sets the option $name to $value.
     *
     * @throws ezcBasePropertyNotFoundException
     *         if the property $name is not defined
     * @throws ezcBaseValueException
     *         if $value is not correct for the property $name
     * @param string $name
     * @param mixed $value
     * @ignore
     */
    public function __set( $name, $value )
    {
        switch ( $name )
        {
            case 'debug':
            case 'preload':
                if ( !is_bool( $value ) )
                {
                    throw new ezcBaseValueException( $name, $value, 'bool' );
                }
                $this->properties[$name] = $value;
                break;

            default:
                throw new ezcBasePropertyNotFoundException( $name );
        }
    }
}
?>
