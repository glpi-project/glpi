<?php
/**
 * File containing the ezcGraphRadialGradient class
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 * 
 *   http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @package Graph
 * @version //autogentag//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
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
 * @version //autogentag//
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
        return sprintf( 'RadialGradient_%d_%d_%d_%d_%.2F_%02x%02x%02x%02x_%02x%02x%02x%02x',
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
