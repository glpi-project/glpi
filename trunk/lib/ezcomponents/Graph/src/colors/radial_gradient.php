<?php
/**
 * File containing the ezcGraphRadialGradient class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Class representing radial gradient fills. For drivers which cannot draw 
 * gradients it falls back to a native ezcGraphColor. In this case the start
 * color of the gradient will be used.
 *
 * @property ezcGraphCoordinate $center
 *           Center point of the gradient.
 * @property int $width
 *           Width of ellipse
 * @property int $height
 *           Width of ellipse
 * @property int $offset
 *           Offset for starting color
 * @property ezcGraphColor $startColor
 *           Starting color of the gradient.
 * @property ezcGraphColor $endColor
 *           Ending color of the gradient.
 *
 * @version 1.5
 * @package Graph
 */
class ezcGraphRadialGradient extends ezcGraphColor
{
    /**
     * Constructor
     * 
     * @param ezcGraphCoordinate $center 
     * @param mixed $width 
     * @param mixed $height 
     * @param ezcGraphColor $startColor 
     * @param ezcGraphColor $endColor 
     * @return void
     */
    public function __construct( ezcGraphCoordinate $center, $width, $height, ezcGraphColor $startColor, ezcGraphColor $endColor )
    {
        $this->properties['center'] = $center;
        $this->properties['width'] = (float) $width;
        $this->properties['height'] = (float) $height;
        $this->properties['offset'] = 0;
        $this->properties['startColor'] = $startColor;
        $this->properties['endColor'] = $endColor;
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
            case 'center':
                if ( !$propertyValue instanceof ezcGraphCoordinate )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'ezcGraphCoordinate' );
                }
                else
                {
                    $this->properties['center'] = $propertyValue;
                }
                break;
            case 'width':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue <= 0 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'float > 0' );
                }

                $this->properties['width'] = (float) $propertyValue;
                break;
            case 'height':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue <= 0 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'float > 0' );
                }

                $this->properties['height'] = (float) $propertyValue;
                break;
            case 'offset':
                if ( !is_numeric( $propertyValue ) ||
                     ( $propertyValue < 0 ) || 
                     ( $propertyValue > 1 ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, '0 <= float <= 1' );
                }

                $this->properties['offset'] = $propertyValue;
                break;
            case 'startColor':
                $this->properties['startColor'] = ezcGraphColor::create( $propertyValue );
                break;
            case 'endColor':
                $this->properties['endColor'] = ezcGraphColor::create( $propertyValue );
                break;
        }
    }

    /**
     * __get 
     * 
     * @param mixed $propertyName 
     * @throws ezcBasePropertyNotFoundException
     *          If a the value for the property options is not an instance of
     * @return mixed
     * @ignore
     */
    public function __get( $propertyName )
    {
        switch ( $propertyName )
        {
            case 'red':
            case 'green':
            case 'blue':
            case 'alpha':
                // Fallback to native color
                return $this->properties['startColor']->$propertyName;
            default:
                if ( isset( $this->properties[$propertyName] ) )
                {
                    return $this->properties[$propertyName];
                }
                else
                {
                    throw new ezcBasePropertyNotFoundException( $propertyName );
                }
        }
    }

    /**
     * Returns a unique string representation for the gradient.
     * 
     * @access public
     * @return void
     */
    public function __toString()
    {
        return sprintf( 'RadialGradient_%d_%d_%d_%d_%.2f_%02x%02x%02x%02x_%02x%02x%02x%02x',
            $this->properties['center']->x,
            $this->properties['center']->y,
            $this->properties['width'],
            $this->properties['height'],
            $this->properties['offset'],
            $this->properties['startColor']->red,
            $this->properties['startColor']->green,
            $this->properties['startColor']->blue,
            $this->properties['startColor']->alpha,
            $this->properties['endColor']->red,
            $this->properties['endColor']->green,
            $this->properties['endColor']->blue,
            $this->properties['endColor']->alpha
        );
    }
}
?>
