<?php
/**
 * File containing the ezcGraphLinearGradient class
 *
 * @package Graph
 * @version 1.5
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Class representing linear gradient fills. For drivers which cannot draw
 * gradients it falls back to a native {@link ezcGraphColor}. In this case the
 * start color of the gradient will be used.
 *
 * @property ezcGraphCoordinate $startPoint
 *           Starting point of the gradient.
 * @property ezcGraphCoordinate $endPoint
 *           Ending point of the gradient.
 * @property ezcGraphColor $startColor
 *           Starting color of the gradient.
 * @property ezcGraphColor $endColor
 *           Ending color of the gradient.
 *
 * @version 1.5
 * @package Graph
 */
class ezcGraphLinearGradient extends ezcGraphColor
{
    /**
     * Constructor
     * 
     * @param ezcGraphCoordinate $startPoint 
     * @param ezcGraphCoordinate $endPoint 
     * @param ezcGraphColor $startColor 
     * @param ezcGraphColor $endColor 
     * @return void
     */
    public function __construct( ezcGraphCoordinate $startPoint, ezcGraphCoordinate $endPoint, ezcGraphColor $startColor, ezcGraphColor $endColor )
    {
        $this->properties['startColor'] = $startColor;
        $this->properties['endColor'] = $endColor;
        $this->properties['startPoint'] = $startPoint;
        $this->properties['endPoint'] = $endPoint;
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
            case 'startPoint':
                if ( !$propertyValue instanceof ezcGraphCoordinate )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'ezcGraphCoordinate' );
                }
                else
                {
                    $this->properties['startPoint'] = $propertyValue;
                }
                break;
            case 'endPoint':
                if ( !$propertyValue instanceof ezcGraphCoordinate )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'ezcGraphCoordinate' );
                }
                else
                {
                    $this->properties['endPoint'] = $propertyValue;
                }
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
        return sprintf( 'LinearGradient_%d_%d_%d_%d_%02x%02x%02x%02x_%02x%02x%02x%02x',
            $this->properties['startPoint']->x,
            $this->properties['startPoint']->y,
            $this->properties['endPoint']->x,
            $this->properties['endPoint']->y,
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
