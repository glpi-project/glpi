<?php
/**
 * File containing the ezcGraphColor class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * ezcGraphColor 
 *
 * Struct for representing colors in ezcGraph. A color is defined using the
 * common RGBA model with integer values between 0 and 255. An alpha value 
 * of zero means full opacity, while 255 means full transparency.
 *
 * @property integer $red
 *           Red RGBA value of color.
 * @property integer $green
 *           Green RGBA value of color.
 * @property integer $blue
 *           Blue RGBA value of color.
 * @property integer $alpha
 *           Alpha RGBA value of color.
 *
 * @version 1.5
 * @package Graph
 */
class ezcGraphColor extends ezcBaseOptions
{
    /**
     * Constructor
     * 
     * @param array $options Default option array
     * @return void
     * @ignore
     */
    public function __construct( array $options = array() )
    {
        $this->properties['red']   = 0;
        $this->properties['green'] = 0;
        $this->properties['blue']  = 0;
        $this->properties['alpha'] = 0;

        parent::__construct( $options );
    }

    /**
     * __set 
     * 
     * @param mixed $propertyName 
     * @param mixed $propertyValue 
     * @throws ezcBaseValueException
     *          If a submitted parameter was out of range or type.
     * @throws ezcBasePropertyNotFoundException
     *          If a the value for the property options is not an instance of
     * @return void
     * @ignore
     */
    public function __set( $propertyName, $propertyValue )
    {
        switch ( $propertyName )
        {
            case 'red':
            case 'green':
            case 'blue':
            case 'alpha':
                if ( !is_numeric( $propertyValue ) || 
                     ( $propertyValue < 0 ) || 
                     ( $propertyValue > 255 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, '0 <= int <= 255' );
                }

                $this->properties[$propertyName] = (int) $propertyValue;
                break;
            default:
                throw new ezcBasePropertyNotFoundException( $propertyName );
                break;
        }
    }

    /**
     * Creates an ezcGraphColor object from a hexadecimal color representation
     * 
     * @param mixed $string Hexadecimal color representation
     * @return ezcGraphColor
     */
    static public function fromHex( $string ) 
    {
        // Remove trailing #
        if ( $string[0] === '#' )
        {
            $string = substr( $string, 1 );
        }
        
        // Iterate over chunks and convert to integer
        $color = new ezcGraphColor();
        $keys = array( 'red', 'green', 'blue', 'alpha' );
        foreach ( str_split( $string, 2) as $nr => $hexValue )
        {
            if ( isset( $keys[$nr] ) ) 
            {
                $key = $keys[$nr];
                $color->$key = hexdec( $hexValue ) % 256;
            }
        }
        
        // Set missing values to zero
        for ( ++$nr; $nr < count( $keys ); ++$nr )
        {
            $key = $keys[$nr];
            $color->$key = 0;
        }

        return $color;
    }

    /**
     * Creates an ezcGraphColor object from an array of integers
     * 
     * @param array $array Array of integer color values
     * @return ezcGraphColor
     */
    static public function fromIntegerArray( array $array )
    {
        // Iterate over array elements
        $color = new ezcGraphColor();
        $keys = array( 'red', 'green', 'blue', 'alpha' );
        $nr = 0;
        foreach ( $array as $colorValue )
        {
            if ( isset( $keys[$nr] ) ) 
            {
                $key = $keys[$nr++];
                $color->$key = ( (int) $colorValue ) % 256;
            }
        }
        
        // Set missing values to zero
        for ( $nr; $nr < count( $keys ); ++$nr )
        {
            $key = $keys[$nr];
            $color->$key = 0;
        }

        return $color;
    }

    /**
     * Creates an ezcGraphColor object from an array of floats
     * 
     * @param array $array Array of float color values
     * @return ezcGraphColor
     */
    static public function fromFloatArray( array $array )
    {
        // Iterate over array elements
        $color = new ezcGraphColor();
        $keys = array( 'red', 'green', 'blue', 'alpha' );
        $nr = 0;
        foreach ( $array as $colorValue )
        {
            if ( isset( $keys[$nr] ) ) 
            {
                $key = $keys[$nr++];
                $color->$key = ( (float) $colorValue * 255 ) % 256;
            }
        }
        
        // Set missing values to zero
        for ( $nr; $nr < count( $keys ); ++$nr )
        {
            $key = $keys[$nr];
            $color->$key = 0;
        }

        return $color;
    }

    /**
     * Tries to parse provided color value
     *
     * This method can be used to create a color struct from arbritrary color
     * representations. The following values are accepted
     *
     * - Hexadecimal color definitions, like known from HTML, CSS and SVG
     *
     *   Color definitions like #FF0000, with and and without number sign,
     *   where each pair of bytes is interpreted as a color value for the
     *   channels RGB(A). These color values may contain up to 4 values, where
     *   the last value is considered as the alpha channel.
     *
     * - Array of integers
     *
     *   If an array of integers is provided as input teh value in each channel
     *   may be in the span [0 - 255] and is assigned to the color channels
     *   RGB(A). Up to four values are used from the array.
     * 
     * - Array of floats
     *
     *   If an array of floats is provided as input teh value in each channel
     *   may be in the span [0 - 1] and is assigned to the color channels
     *   RGB(A). Up to four values are used from the array.
     * 
     * @param mixed $color Some kind of color definition
     * @return ezcGraphColor
     */
    static public function create( $color )
    {
        if ( $color instanceof ezcGraphColor )
        {
            return $color;
        }
        elseif ( is_string( $color ) )
        {
            return ezcGraphColor::fromHex( $color );
        }
        elseif ( is_array( $color ) )
        {
            $testElement = reset( $color );
            if ( is_int( $testElement ) )
            {
                return ezcGraphColor::fromIntegerArray( $color );
            } 
            else 
            {
                return ezcGraphColor::fromFloatArray( $color );
            }
        }
        else
        {
            throw new ezcGraphUnknownColorDefinitionException( $color );
        }
    }

    /**
     * Returns a copy of the current color made more transparent by the given
     * factor
     * 
     * @param mixed $value  Percent to make color mor transparent
     * @return ezcGraphColor New color
     */
    public function transparent( $value )
    {
        $color = clone $this;

        $color->alpha = 255 - (int) round( ( 255 - $this->alpha ) * ( 1 - $value ) );

        return $color;
    }

    /**
     * Inverts and returns a copy of the current color
     * 
     * @return ezcGraphColor New Color
     */
    public function invert()
    {
        $color = new ezcGraphColor();
               
        $color->red   = 255 - $this->red;
        $color->green = 255 - $this->green;
        $color->blue  = 255 - $this->blue;
        $color->alpha = $this->alpha;

        return $color;
    }

    /**
     * Returns a copy of the current color darkened by the given factor
     * 
     * @param float $value Percent to darken the color
     * @return ezcGraphColor New color
     */
    public function darken( $value )
    {
        $color = clone $this;

        $value        = 1 - $value;
        $color->red   = min( 255, max( 0, (int) round( $this->red * $value ) ) );
        $color->green = min( 255, max( 0, (int) round( $this->green * $value ) ) );
        $color->blue  = min( 255, max( 0, (int) round( $this->blue * $value ) ) );

        return $color;
    }
}

?>
