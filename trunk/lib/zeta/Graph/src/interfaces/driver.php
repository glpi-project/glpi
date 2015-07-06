<?php
/**
 * File containing the abstract ezcGraphDriver class
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
 * Abstract class to be extended for ezcGraph output drivers.
 *
 * @version //autogentag//
 * @package Graph
 */
abstract class ezcGraphDriver
{
    /**
     * Driveroptions
     * 
     * @var ezcDriverOptions
     */
    protected $options;
    
    /**
     * Constructor
     * 
     * @param array $options Default option array
     * @return void
     * @ignore
     */
    abstract public function __construct( array $options = array() );
    
    /**
     * Options write access
     * 
     * @throws ezcBasePropertyNotFoundException
     *          If Option could not be found
     * @throws ezcBaseValueException
     *          If value is out of range
     * @param mixed $propertyName   Option name
     * @param mixed $propertyValue  Option value;
     * @return mixed
     * @ignore
     */
    public function __set( $propertyName, $propertyValue ) 
    {
        switch ( $propertyName ) {
            case 'options':
                if ( $propertyValue instanceof ezcGraphDriverOptions )
                {
                    $this->options = $propertyValue;
                }
                else
                {
                    throw new ezcBaseValueException( "options", $propertyValue, "instanceof ezcGraphOptions" );
                }
                break;

            default:
                throw new ezcBasePropertyNotFoundException( $propertyName );
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
            case 'options':
                return $this->options;
            default:
                throw new ezcBasePropertyNotFoundException( $propertyName );
        }
    }

    /**
     * Reduces the size of a polygon
     *
     * The method takes a polygon defined by a list of points and reduces its 
     * size by moving all lines to the middle by the given $size value.
     *
     * The detection of the inner side of the polygon depends on the angle at 
     * each edge point. This method will always work for 3 edged polygones, 
     * because the smaller angle will always be on the inner side. For 
     * polygons with more then 3 edges this method may fail. For ezcGraph this
     * is a valid simplification, because we do not have any polygones which 
     * have an inner angle >= 180 degrees.
     * 
     * @param array(ezcGraphCoordinate) $points 
     * @param float $size 
     * @throws ezcGraphReducementFailedException
     * @return array( ezcGraphCoordinate )
     */
    protected function reducePolygonSize( array $points, $size )
    {
        $pointCount = count( $points );

        // Build normalized vectors between polygon edge points
        $vectors = array();
        $vectorLength = array();
        for ( $i = 0; $i < $pointCount; ++$i )
        {
            $nextPoint = ( $i + 1 ) % $pointCount;
            $vectors[$i] = ezcGraphVector::fromCoordinate( $points[$nextPoint] )
                            ->sub( $points[$i] );

            // Throw exception if polygon is too small to reduce
            $vectorLength[$i] = $vectors[$i]->length();
            if ( $vectorLength[$i] < $size )
            {
                throw new ezcGraphReducementFailedException();
            }
            $vectors[$i]->unify();

            // Remove point from list if it the same as the next point
            if ( ( $vectors[$i]->x == $vectors[$i]->y ) && ( $vectors[$i]->x == 0 ) )
            {
                $pointCount--;
                if ( $i === 0 ) 
                {
                    $points = array_slice( $points, $i + 1 );
                }
                else
                {
                    $points = array_merge(
                        array_slice( $points, 0, $i ),
                        array_slice( $points, $i + 1 )
                    );
                }
                $i--;
            }
        }

        // Remove vectors and appendant point, if local angle equals zero 
        // dergrees.
        for ( $i = 0; $i < $pointCount; ++$i ) 
        {
            $nextPoint = ( $i + 1 ) % $pointCount;

            if ( ( abs( $vectors[$i]->x - $vectors[$nextPoint]->x ) < .0001 ) &&
                 ( abs( $vectors[$i]->y - $vectors[$nextPoint]->y ) < .0001 ) )
            {
                $pointCount--;

                $points = array_merge(
                    array_slice( $points, 0, $i + 1 ),
                    array_slice( $points, $i + 2 )
                );
                $vectors = array_merge(
                    array_slice( $vectors, 0, $i + 1 ),
                    array_slice( $vectors, $i + 2 )
                );
                $i--;
            }
        }

        // No reducements for lines
        if ( $pointCount <= 2 )
        {
            return $points;
        }

        // Determine one of the angles - we need to know where the smaller
        // angle is, to determine if the inner side of the polygon is on
        // the left or right hand.
        // 
        // This is a valid simplification for ezcGraph(, for now).
        // 
        // The sign of the scalar products results indicates on which site
        // the smaller angle is, when comparing the orthogonale vector of 
        // one of the vectors with the other. Why? .. use pen and paper ..
        // 
        // It is sufficant to do this once before iterating over the points, 
        // because the inner side of the polygon is on the same side of the 
        // point for each point.
        $last = 0;
        $next = 1;

        $sign = ( 
                -$vectors[$last]->y * $vectors[$next]->x +
                $vectors[$last]->x * $vectors[$next]->y 
            ) < 0 ? 1 : -1;

        // Move points to center
        $newPoints = array();
        for ( $i = 0; $i < $pointCount; ++$i )
        {
            $last = $i;
            $next = ( $i + 1 ) % $pointCount;

            // Orthogonal vector with direction based on the side of the inner
            // angle
            $v = clone $vectors[$next];
            if ( $sign > 0 )
            {
                $v->rotateCounterClockwise()->scalar( $size );
            }
            else
            {
                $v->rotateClockwise()->scalar( $size );
            }

            // get last vector not pointing in reverse direction
            $lastVector = clone $vectors[$last];
            $lastVector->scalar( -1 );

            // Calculate new point: Move point to the center site of the 
            // polygon using the normalized orthogonal vectors next to the 
            // point and the size as distance to move.
            // point + v + size / tan( angle / 2 ) * startVector
            $newPoint = clone $vectors[$next];
            $v  ->add( 
                $newPoint
                    ->scalar( 
                        $size / 
                        tan( 
                            $lastVector->angle( $vectors[$next] ) / 2
                        ) 
                    ) 
            );

            // A fast guess: If the movement of the point exceeds the length of
            // the surrounding edge vectors the angle was to small to perform a
            // valid size reducement. In this case we just reduce the length of
            // the movement to the minimal length of the surrounding vectors.
            // This should fit in most cases.
            //
            // The correct way to check would be a test, if the calculated
            // point is still in the original polygon, but a test for a point
            // in a polygon is too expensive.
            $movement = $v->length();
            if ( ( $movement > $vectorLength[$last] ) &&
                 ( $movement > $vectorLength[$next] ) )
            {
                $v->unify()->scalar( min( $vectorLength[$last], $vectorLength[$next] ) );
            }

            $newPoints[$next] = $v->add( $points[$next] );
        }

        return $newPoints;
    }

    /**
     * Reduce the size of an ellipse
     *
     * The method returns a the edgepoints and angles for an ellipse where all 
     * borders are moved to the inner side of the ellipse by the give $size 
     * value.
     *
     * The method returns an 
     * array (
     *      'center' => (ezcGraphCoordinate) New center point,
     *      'start' => (ezcGraphCoordinate) New outer start point,
     *      'end' => (ezcGraphCoordinate) New outer end point,
     * )
     * 
     * @param ezcGraphCoordinate $center 
     * @param float $width
     * @param float $height
     * @param float $startAngle 
     * @param float $endAngle 
     * @param float $size 
     * @throws ezcGraphReducementFailedException
     * @return array
     */
    protected function reduceEllipseSize( ezcGraphCoordinate $center, $width, $height, $startAngle, $endAngle, $size )
    {
        $oldStartPoint = new ezcGraphVector(
            $width * cos( deg2rad( $startAngle ) ) / 2,
            $height * sin( deg2rad( $startAngle ) ) / 2
        );

        $oldEndPoint = new ezcGraphVector(
            $width * cos( deg2rad( $endAngle ) ) / 2,
            $height * sin( deg2rad( $endAngle ) ) / 2
        );

        // We always need radian values..
        $degAngle = abs( $endAngle - $startAngle );
        $startAngle = deg2rad( $startAngle );
        $endAngle = deg2rad( $endAngle );

        // Calculate normalized vectors for the lines spanning the ellipse
        $unifiedStartVector = ezcGraphVector::fromCoordinate( $oldStartPoint )->unify();
        $unifiedEndVector = ezcGraphVector::fromCoordinate( $oldEndPoint )->unify();
        $startVector = ezcGraphVector::fromCoordinate( $oldStartPoint );
        $endVector = ezcGraphVector::fromCoordinate( $oldEndPoint );

        $oldStartPoint->add( $center );
        $oldEndPoint->add( $center );

        // Use orthogonal vectors of normalized ellipse spanning vectors to 
        $v = clone $unifiedStartVector;
        $v->rotateClockwise()->scalar( $size );

        // calculate new center point
        // center + v + size / tan( angle / 2 ) * startVector
        $centerMovement = clone $unifiedStartVector;
        $newCenter = $v->add( $centerMovement->scalar( $size / tan( ( $endAngle - $startAngle ) / 2 ) ) )->add( $center );

        // Test if center is still inside the ellipse, otherwise the sector 
        // was to small to be reduced
        $innerBoundingBoxSize = 0.7 * min( $width, $height );
        if ( ( $newCenter->x < ( $center->x + $innerBoundingBoxSize ) ) &&
             ( $newCenter->x > ( $center->x - $innerBoundingBoxSize ) ) &&
             ( $newCenter->y < ( $center->y + $innerBoundingBoxSize ) ) &&
             ( $newCenter->y > ( $center->y - $innerBoundingBoxSize ) ) )
        {
            // Point is in inner bounding box -> everything is OK
        }
        elseif ( ( $newCenter->x < ( $center->x - $width ) ) ||
                 ( $newCenter->x > ( $center->x + $width ) ) ||
                 ( $newCenter->y < ( $center->y - $height ) ) ||
                 ( $newCenter->y > ( $center->y + $height ) ) )
        {
            // Quick outer boundings check
            if ( $degAngle > 180 )
            {
                // Use old center for very big angles
                $newCenter = clone $center;
            }
            else
            {
                // Do not draw for very small angles
                throw new ezcGraphReducementFailedException();
            }
        }
        else
        {
            // Perform exact check
            $distance = new ezcGraphVector(
                $newCenter->x - $center->x,
                $newCenter->y - $center->y
            );

            // Convert elipse to circle for correct angle calculation
            $direction = clone $distance;
            $direction->y *= ( $width / $height );
            $angle = $direction->angle( new ezcGraphVector( 0, 1 ) );

            $outerPoint = new ezcGraphVector(
                sin( $angle ) * $width / 2,
                cos( $angle ) * $height / 2
            );

            // Point is not in ellipse any more
            if ( abs( $distance->x ) > abs( $outerPoint->x ) )
            {
                if ( $degAngle > 180 )
                {
                    // Use old center for very big angles
                    $newCenter = clone $center;
                }
                else
                {
                    // Do not draw for very small angles
                    throw new ezcGraphReducementFailedException();
                }
            }
        }

        // Use start spanning vector and its orthogonal vector to calculate 
        // new start point
        $newStartPoint = clone $oldStartPoint;

        // Create tangent vector from tangent angle
        
        // Ellipse tangent factor
        $ellipseTangentFactor = sqrt(
            pow( $height, 2 ) *
                pow( cos( $startAngle ), 2 ) +
            pow( $width, 2 ) *
                pow( sin( $startAngle ), 2 )
        );
        $ellipseTangentVector = new ezcGraphVector(
            $width * -sin( $startAngle ) / $ellipseTangentFactor,
            $height * cos( $startAngle ) / $ellipseTangentFactor
        );

        // Reverse spanning vector
        $innerVector = clone $unifiedStartVector;
        $innerVector->scalar( $size )->scalar( -1 );

        $newStartPoint->add( $innerVector)->add( $ellipseTangentVector->scalar( $size ) );
        $newStartVector = clone $startVector;
        $newStartVector->add( $ellipseTangentVector );

        // Use end spanning vector and its orthogonal vector to calculate 
        // new end point
        $newEndPoint = clone $oldEndPoint;

        // Create tangent vector from tangent angle
        
        // Ellipse tangent factor
        $ellipseTangentFactor = sqrt(
            pow( $height, 2 ) *
                pow( cos( $endAngle ), 2 ) +
            pow( $width, 2 ) *
                pow( sin( $endAngle ), 2 )
        );
        $ellipseTangentVector = new ezcGraphVector(
            $width * -sin( $endAngle ) / $ellipseTangentFactor,
            $height * cos( $endAngle ) / $ellipseTangentFactor
        );

        // Reverse spanning vector
        $innerVector = clone $unifiedEndVector;
        $innerVector->scalar( $size )->scalar( -1 );

        $newEndPoint->add( $innerVector )->add( $ellipseTangentVector->scalar( $size )->scalar( -1 ) );
        $newEndVector = clone $endVector;
        $newEndVector->add( $ellipseTangentVector );

        return array(
            'center' => $newCenter,
            'start' => $newStartPoint,
            'end' => $newEndPoint,
            'startAngle' => rad2deg( $startAngle + $startVector->angle( $newStartVector ) ),
            'endAngle' => rad2deg( $endAngle - $endVector->angle( $newEndVector ) ),
        );
    }

    /**
     * Draws a single polygon. 
     * 
     * @param array $points Point array
     * @param ezcGraphColor $color Polygon color
     * @param mixed $filled Filled
     * @param float $thickness Line thickness
     * @return void
     */
    abstract public function drawPolygon( array $points, ezcGraphColor $color, $filled = true, $thickness = 1. );
    
    /**
     * Draws a line 
     * 
     * @param ezcGraphCoordinate $start Start point
     * @param ezcGraphCoordinate $end End point
     * @param ezcGraphColor $color Line color
     * @param float $thickness Line thickness
     * @return void
     */
    abstract public function drawLine( ezcGraphCoordinate $start, ezcGraphCoordinate $end, ezcGraphColor $color, $thickness = 1. );
    
    /**
     * Returns boundings of text depending on the available font extension
     * 
     * @param float $size Textsize
     * @param ezcGraphFontOptions $font Font
     * @param string $text Text
     * @return ezcGraphBoundings Boundings of text
     */
    abstract protected function getTextBoundings( $size, ezcGraphFontOptions $font, $text );
    
    /**
     * Test if string fits in a box with given font size
     *
     * This method splits the text up into tokens and tries to wrap the text
     * in an optimal way to fit in the Box defined by width and height.
     * 
     * If the text fits into the box an array with lines is returned, which 
     * can be used to render the text later:
     *  array(
     *      // Lines
     *      array( 'word', 'word', .. ),
     *  )
     * Otherwise the function will return false.
     *
     * @param string $string Text
     * @param ezcGraphCoordinate $position Topleft position of the text box
     * @param float $width Width of textbox
     * @param float $height Height of textbox
     * @param int $size Fontsize
     * @return mixed Array with lines or false on failure
     */
    protected function testFitStringInTextBox( $string, ezcGraphCoordinate $position, $width, $height, $size )
    {
        // Tokenize String
        $tokens = preg_split( '/\s+/', $string );
        $initialHeight = $height;

        $lines = array( array() );
        $line = 0;
        foreach ( $tokens as $nr => $token )
        {
            // Add token to tested line
            $selectedLine = $lines[$line];
            $selectedLine[] = $token;

            $boundings = $this->getTextBoundings( $size, $this->options->font, implode( ' ', $selectedLine ) );
            // Check if line is too long
            if ( $boundings->width > $width )
            {
                if ( count( $selectedLine ) == 1 )
                {
                    // Return false if one single word does not fit into one line
                    // Scale down font size to fit this word in one line
                    return $width / $boundings->width;
                }
                else
                {
                    // Put word in next line instead and reduce available height by used space
                    $lines[++$line][] = $token;
                    $height -= $size * ( 1 + $this->options->lineSpacing );
                }
            }
            else
            {
                // Everything is ok - put token in this line
                $lines[$line][] = $token;
            }
            
            // Return false if text exceeds vertical limit
            if ( $size > $height )
            {
                return 1;
            }
        }

        // Check width of last line
        $boundings = $this->getTextBoundings( $size, $this->options->font, implode( ' ', $lines[$line] ) );
        if ( $boundings->width > $width )
        {
            return 1;
        }

        // It seems to fit - return line array
        return $lines;
    }

    /**
     * If it is allow to shortened the string, this method tries to extract as
     * many chars as possible to display a decent amount of characters.
     *
     * If no complete token (word) does fit, the largest possible amount of 
     * chars from the first word are taken. If the amount of chars is bigger
     * then strlen( shortenedStringPostFix ) * 2 the last chars are replace by
     * the postfix.
     *
     * If one complete word fits the box as many words are taken as possible 
     * including a appended shortenedStringPostFix.
     * 
     * @param mixed $string 
     * @param ezcGraphCoordinate $position 
     * @param mixed $width 
     * @param mixed $height 
     * @param mixed $size 
     * @access protected
     * @return void
     */
    protected function tryFitShortenedString( $string, ezcGraphCoordinate $position, $width, $height, $size )
    {
        $tokens = preg_split( '/\s+/', $string );

        // Try to fit a complete word first
        $boundings = $this->getTextBoundings( 
            $size, 
            $this->options->font, 
            reset( $tokens ) . ( $postfix = $this->options->autoShortenStringPostFix )
        );

        if ( $boundings->width > $width )
        {
            // Not even one word fits the box
            $word = reset( $tokens );

            // Test if first character fits the box
            $boundigs = $this->getTextBoundings(
                $size,
                $this->options->font,
                $hit = $word[0]
            );

            if ( $boundigs->width > $width )
            {
                // That is a really small box.
                throw new ezcGraphFontRenderingException( $string, $size, $width, $height );
            }

            // Try to put more charactes in there
            $postLength = strlen( $postfix );
            $wordLength = strlen( $word );
            for ( $i = 2; $i <= $wordLength; ++$i )
            {
                $string = substr( $word, 0, $i );
                if ( strlen( $string ) > ( $postLength << 1 ) )
                {
                    $string = substr( $string, 0, -$postLength ) . $postfix;
                }

                $boundigs = $this->getTextBoundings( $size, $this->options->font, $string );

                if ( $boundigs->width < $width )
                {
                    $hit = $string;
                }
                else
                {
                    // Use last string which fit
                    break;
                }
            }
        }
        else
        {
            // Try to use as many words as possible
            $hit = reset( $tokens );

            for ( $i = 2; $i < count( $tokens ); ++$i )
            {
                $string = implode( ' ', array_slice( $tokens, 0, $i ) ) . 
                    $postfix;

                $boundings = $this->getTextBoundings( $size, $this->options->font, $string );

                if ( $boundings->width <= $width )
                {
                    $hit .= ' ' . $tokens[$i - 1];
                }
                else
                {
                    // Use last valid hit
                    break;
                }
            }

            $hit .= $postfix;
        }

        return array( array( $hit ) );
    }

    /**
     * Writes text in a box of desired size
     * 
     * @param string $string Text
     * @param ezcGraphCoordinate $position Top left position
     * @param float $width Width of text box
     * @param float $height Height of text box
     * @param int $align Alignement of text
     * @param ezcGraphRotation $rotation
     * @return void
     */
    abstract public function drawTextBox( $string, ezcGraphCoordinate $position, $width, $height, $align, ezcGraphRotation $rotation = null );
    
    /**
     * Draws a sector of cirlce
     * 
     * @param ezcGraphCoordinate $center Center of circle
     * @param mixed $width Width
     * @param mixed $height Height
     * @param mixed $startAngle Start angle of circle sector
     * @param mixed $endAngle End angle of circle sector
     * @param ezcGraphColor $color Color
     * @param mixed $filled Filled
     * @return void
     */
    abstract public function drawCircleSector( ezcGraphCoordinate $center, $width, $height, $startAngle, $endAngle, ezcGraphColor $color, $filled = true );
    
    /**
     * Draws a circular arc
     * 
     * @param ezcGraphCoordinate $center Center of ellipse
     * @param integer $width Width of ellipse
     * @param integer $height Height of ellipse
     * @param integer $size Height of border
     * @param float $startAngle Starting angle of circle sector
     * @param float $endAngle Ending angle of circle sector
     * @param ezcGraphColor $color Color of Border
     * @param bool $filled Fill state
     * @return void
     */
    abstract public function drawCircularArc( ezcGraphCoordinate $center, $width, $height, $size, $startAngle, $endAngle, ezcGraphColor $color, $filled = true );
    
    /**
     * Draw circle 
     * 
     * @param ezcGraphCoordinate $center Center of ellipse
     * @param mixed $width Width of ellipse
     * @param mixed $height height of ellipse
     * @param ezcGraphColor $color Color
     * @param mixed $filled Filled
     * @return void
     */
    abstract public function drawCircle( ezcGraphCoordinate $center, $width, $height, ezcGraphColor $color, $filled = true );
    
    /**
     * Draw an image 
     * 
     * @param mixed $file Image file
     * @param ezcGraphCoordinate $position Top left position
     * @param mixed $width Width of image in destination image
     * @param mixed $height Height of image in destination image
     * @return void
     */
    abstract public function drawImage( $file, ezcGraphCoordinate $position, $width, $height );

    /**
     * Return mime type for current image format
     * 
     * @return string
     */
    abstract public function getMimeType();

    /**
     * Render image directly to output
     *
     * The method renders the image directly to the standard output. You 
     * normally do not want to use this function, because it makes it harder 
     * to proper cache the generated graphs.
     * 
     * @return void
     */
    public function renderToOutput()
    {
        header( 'Content-Type: ' . $this->getMimeType() );
        $this->render( 'php://output' );
    }

    /**
     * Finally save image
     * 
     * @param string $file Destination filename
     * @return void
     */
    abstract public function render( $file );
}

?>
