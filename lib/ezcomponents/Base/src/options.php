<?php
/**
 * File containing the ezcBaseOptions class.
 *
 * @package Base
 * @version 1.8
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Base options class for all eZ components.
 *
 * @package Base
 * @version 1.8
 */
abstract class ezcBaseOptions implements ArrayAccess
{
    /**
     * Container to hold the properties
     *
     * @var array(string=>mixed)
     */
    protected $properties;

    /**
     * Construct a new options object.
     * Options are constructed from an option array by default. The constructor
     * automatically passes the given options to the __set() method to set them
     * in the class.
     *
     * @throws ezcBasePropertyNotFoundException
     *         If trying to access a non existent property.
     * @throws ezcBaseValueException
     *         If the value for a property is out of range.
     * @param array(string=>mixed) $options The initial options to set.
     */
    public function __construct( array $options = array() )
    {
        foreach ( $options as $option => $value )
        {
            $this->__set( $option, $value );
        }
    }

    /**
     * Merge an array into the actual options object.
     * This method merges an array of new options into the actual options object.
     *
     * @throws ezcBasePropertyNotFoundException
     *         If trying to access a non existent property.
     * @throws ezcBaseValueException
     *         If the value for a property is out of range.
     * @param array(string=>mixed) $newOptions The new options.
     */
    public function merge( array $newOptions )
    {
        foreach ( $newOptions as $key => $value )
        {
            $this->__set( $key, $value );
        }
    }

    /**
     * Property get access.
     * Simply returns a given option.
     *
     * @throws ezcBasePropertyNotFoundException
     *         If a the value for the property options is not an instance of
     * @param string $propertyName The name of the option to get.
     * @return mixed The option value.
     * @ignore
     *
     * @throws ezcBasePropertyNotFoundException
     *         if the given property does not exist.
     * @throws ezcBasePropertyPermissionException
     *         if the property to be set is a write-only property.
     */
    public function __get( $propertyName )
    {
        if ( $this->__isset( $propertyName ) === true )
        {
            return $this->properties[$propertyName];
        }
        throw new ezcBasePropertyNotFoundException( $propertyName );
    }

    /**
     * Sets an option.
     * This method is called when an option is set.
     *
     * @param string $propertyName  The name of the option to set.
     * @param mixed $propertyValue The option value.
     * @ignore
     *
     * @throws ezcBasePropertyNotFoundException
     *         if the given property does not exist.
     * @throws ezcBaseValueException
     *         if the value to be assigned to a property is invalid.
     * @throws ezcBasePropertyPermissionException
     *         if the property to be set is a read-only property.
     */
    abstract public function __set( $propertyName, $propertyValue );

    /**
     * Returns if a option exists.
     *
     * @param string $propertyName Option name to check for.
     * @return bool Whether the option exists.
     * @ignore
     */
    public function __isset( $propertyName )
    {
        return array_key_exists( $propertyName, $this->properties );
    }

    /**
     * Returns if an option exists.
     * Allows isset() using ArrayAccess.
     *
     * @param string $propertyName The name of the option to get.
     * @return bool Whether the option exists.
     */
    public function offsetExists( $propertyName )
    {
        return $this->__isset( $propertyName );
    }

    /**
     * Returns an option value.
     * Get an option value by ArrayAccess.
     *
     * @throws ezcBasePropertyNotFoundException
     *         If $propertyName is not a key in the $properties array.
     * @param string $propertyName The name of the option to get.
     * @return mixed The option value.
     */
    public function offsetGet( $propertyName )
    {
        return $this->__get( $propertyName );
    }

    /**
     * Set an option.
     * Sets an option using ArrayAccess.
     *
     * @throws ezcBasePropertyNotFoundException
     *         If $propertyName is not a key in the $properties array.
     * @throws ezcBaseValueException
     *         If the value for a property is out of range.
     * @param string $propertyName The name of the option to set.
     * @param mixed $propertyValue The value for the option.
     */
    public function offsetSet( $propertyName, $propertyValue )
    {
        $this->__set( $propertyName, $propertyValue );
    }

    /**
     * Unset an option.
     * Unsets an option using ArrayAccess.
     *
     * @throws ezcBasePropertyNotFoundException
     *         If $propertyName is not a key in the $properties array.
     * @throws ezcBaseValueException
     *         If a the value for a property is out of range.
     * @param string $propertyName The name of the option to unset.
     */
    public function offsetUnset( $propertyName )
    {
        $this->__set( $propertyName, null );
    }
}
?>
