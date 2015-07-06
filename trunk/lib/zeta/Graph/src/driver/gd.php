<?php
/**
 * File containing the ezcGraphGdDriver class
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
 * Driver using PHPs ext/gd to draw images. The GD extension is available on 
 * nearly all PHP installations, but slow and produces slightly incorrect 
 * results.
 *
 * The driver can make use of the different font extensions available with 
 * ext/gd. It is possible to use Free Type 2, native TTF and PostScript Type 1 
 * fonts.
 *
 * The options of this driver are configured in {@link ezcGraphGdDriverOptions}
 * extending the basic driver options class {@link ezcGraphDriverOptions}.
 *
 * <code>
 *   $graph = new ezcGraphPieChart();
 *   $graph->palette = new ezcGraphPaletteEzGreen();
 *   $graph->title = 'Access statistics';
 *   $graph->legend = false;
 *   
 *   $graph->driver = new ezcGraphGdDriver();
 *   $graph->options->font = 'tutorial_font.ttf';
 *
 *   // Generate a Jpeg with lower quality. The default settings result in a image
 *   // with better quality.
 *   // 
 *   // The reduction of the supersampling to 1 will result in no anti aliasing of
 *   // the image. JPEG is not the optimal format for grapics, PNG is far better for
 *   // this kind of images.
 *   $graph->driver->options->supersampling = 1;
 *   $graph->driver->options->jpegQuality = 100;
 *   $graph->driver->options->imageFormat = IMG_JPEG;
 *   
 *   $graph->data['Access statistics'] = new ezcGraphArrayDataSet( array(
 *       'Mozilla' => 19113,
 *       'Explorer' => 10917,
 *       'Opera' => 1464,
 *       'Safari' => 652,
 *       'Konqueror' => 474,
 *   ) );
 *   
 *   $graph->render( 400, 200, 'tutorial_dirver_gd.jpg' );
 * </code>
 *
 * @version //autogentag//
 * @package Graph
 * @mainclass
 */
class ezcGraphGdDriver extends ezcGraphDriver
{

    /**
     * Image resource
     * 
     * @var resource
     */
    protected $image;

    /**
     * Array with image files to draw
     * 
     * @var array
     */
    protected $preProcessImages = array();

    /**
     * List of strings to draw
     * array ( array(
     *          'text' => array( 'strings' ),
     *          'options' => ezcGraphFontOptions,
     *      )
     * 
     * @var array
     */
    protected $strings = array();

    /**
     * Contains resources for already loaded ps fonts.
     *  array(
     *      path => resource
     *  )
     * 
     * @var array
     */
    protected $psFontResources = array();

    /**
     * Constructor
     * 
     * @param array $options Default option array
     * @return void
     * @ignore
     */
    public function __construct( array $options = array() )
    {
        ezcBase::checkDependency( 'Graph', ezcBase::DEP_PHP_EXTENSION, 'gd' );
        $this->options = new ezcGraphGdDriverOptions( $options );
    }

    /**
     * Returns the image resource to draw on.
     *
     * If no resource exists the image will be created. The size of the 
     * returned image depends on the supersampling factor and the size of the
     * chart.
     * 
     * @return resource
     */
    protected function getImage()
    {
        if ( !isset( $this->image ) )
        {
            $this->image = imagecreatetruecolor( 
                $this->supersample( $this->options->width ), 
                $this->supersample( $this->options->height )
            );

            // Default to a transparent white background
            $bgColor = imagecolorallocatealpha( $this->image, 255, 255, 255, 127 );
            imagealphablending( $this->image, true );
            imagesavealpha( $this->image, true );
            imagefill( $this->image, 1, 1, $bgColor );

            imagesetthickness( 
                $this->image, 
                $this->options->supersampling
            );
        }

        return $this->image;
    }

    /**
     * Allocates a color
     *
     * This function tries to allocate the requested color. If the color 
     * already exists in the imaga it will be reused.
     * 
     * @param ezcGraphColor $color 
     * @return int Color index
     */
    protected function allocate( ezcGraphColor $color )
    {
        $image = $this->getImage();

        if ( $color->alpha > 0 )
        {
            $fetched = imagecolorexactalpha( $image, $color->red, $color->green, $color->blue, $color->alpha / 2 );
            if ( $fetched < 0 )
            {
                $fetched = imagecolorallocatealpha( $image, $color->red, $color->green, $color->blue, $color->alpha / 2 );
            }
            return $fetched;
        }
        else
        {
            $fetched = imagecolorexact( $image, $color->red, $color->green, $color->blue );
            if ( $fetched < 0 )
            {
                $fetched = imagecolorallocate( $image, $color->red, $color->green, $color->blue );
            }
            return $fetched;
        }
    }

    /**
     * Creates an image resource from an image file
     *
     * @param string $file Filename
     * @return resource Image
     */
    protected function imageCreateFrom( $file )
    {
        $data = getimagesize( $file );

        switch ( $data[2] )
        {
            case 1:
                return array(
                    'width' => $data[0],
                    'height' => $data[1],
                    'image' => imagecreatefromgif( $file )
                );
            case 2:
                return array(
                    'width' => $data[0],
                    'height' => $data[1],
                    'image' => imagecreatefromjpeg( $file )
                );
            case 3:
                return array(
                    'width' => $data[0],
                    'height' => $data[1],
                    'image' => imagecreatefrompng( $file )
                );
            default:
                throw new ezcGraphGdDriverUnsupportedImageTypeException( $data[2] );
        }
    }

    /**
     * Supersamples a single coordinate value.
     *
     * Applies supersampling to a single coordinate value.
     * 
     * @param float $value Coordinate value
     * @return float Supersampled coordinate value
     */
    protected function supersample( $value )
    {
        $mod = (int) floor( $this->options->supersampling / 2 );
        return $value * $this->options->supersampling - $mod;
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
    public function drawPolygon( array $points, ezcGraphColor $color, $filled = true, $thickness = 1. )
    {
        $image = $this->getImage();

        $drawColor = $this->allocate( $color );

        // Create point array
        $pointCount = count( $points );
        $pointArray = array();
        for ( $i = 0; $i < $pointCount; ++$i )
        {
            $pointArray[] = $this->supersample( $points[$i]->x );
            $pointArray[] = $this->supersample( $points[$i]->y );
        }

        // Draw polygon
        if ( $filled )
        {
            imagefilledpolygon( $image, $pointArray, $pointCount, $drawColor );
        }
        else
        {
            imagepolygon( $image, $pointArray, $pointCount, $drawColor );
        }

        return $points;
    }
    
    /**
     * Draws a line 
     * 
     * @param ezcGraphCoordinate $start Start point
     * @param ezcGraphCoordinate $end End point
     * @param ezcGraphColor $color Line color
     * @param float $thickness Line thickness
     * @return void
     */
    public function drawLine( ezcGraphCoordinate $start, ezcGraphCoordinate $end, ezcGraphColor $color, $thickness = 1. )
    {
        $image = $this->getImage();

        $drawColor = $this->allocate( $color );

        imagesetthickness( 
            $this->image, 
            $this->options->supersampling * $thickness
        );

        imageline( 
            $image, 
            $this->supersample( $start->x ), 
            $this->supersample( $start->y ), 
            $this->supersample( $end->x ), 
            $this->supersample( $end->y ), 
            $drawColor
        );

        imagesetthickness( 
            $this->image, 
            $this->options->supersampling
        );

        return array();
    }
    
    /**
     * Returns boundings of text depending on the available font extension
     * 
     * @param float $size Textsize
     * @param ezcGraphFontOptions $font Font
     * @param string $text Text
     * @return ezcGraphBoundings Boundings of text
     */
    protected function getTextBoundings( $size, ezcGraphFontOptions $font, $text )
    {
        switch ( $font->type )
        {
            case ezcGraph::PS_FONT:
                if ( !isset( $this->psFontResources[$font->path] ) )
                {
                    $this->psFontResources[$font->path] = imagePsLoadFont( $font->path );
                }

                $boundings = imagePsBBox( $text, $this->psFontResources[$font->path], $size );
                return new ezcGraphBoundings(
                    $boundings[0],
                    $boundings[1],
                    $boundings[2],
                    $boundings[3]
                );
            case ezcGraph::TTF_FONT:
                switch ( true )
                {
                    case ezcBaseFeatures::hasFunction( 'imageftbbox' ) && !$this->options->forceNativeTTF:
                        $boundings = imageFtBBox( $size, 0, $font->path, $text );
                        return new ezcGraphBoundings(
                            $boundings[0],
                            $boundings[1],
                            $boundings[4],
                            $boundings[5]
                        );
                    case ezcBaseFeatures::hasFunction( 'imagettfbbox' ):
                        $boundings = imageTtfBBox( $size, 0, $font->path, $text );
                        return new ezcGraphBoundings(
                            $boundings[0],
                            $boundings[1],
                            $boundings[4],
                            $boundings[5]
                        );
                }
                break;
        }
    }

    /**
     * Render text depending of font type and available font extensions
     * 
     * @param resource $image Image resource
     * @param string $text Text
     * @param int $type Font type
     * @param string $path Font path
     * @param ezcGraphColor $color Font color
     * @param ezcGraphCoordinate $position Position
     * @param float $size Textsize
     * @param ezcGraphRotation $rotation
     *
     * @return void
     */
    protected function renderText( $image, $text, $type, $path, ezcGraphColor $color, ezcGraphCoordinate $position, $size, ezcGraphRotation $rotation = null )
    {
        if ( $rotation !== null )
        {
            // Rotation is relative to top left point of text and not relative
            // to the bounding coordinate system
            $rotation = new ezcGraphRotation(
                $rotation->getRotation(),
                new ezcGraphCoordinate(
                    $rotation->getCenter()->x - $position->x,
                    $rotation->getCenter()->y - $position->y
                )
            );
        }

        switch ( $type )
        {
            case ezcGraph::PS_FONT:
                imagePsText( 
                    $image, 
                    $text, 
                    $this->psFontResources[$path], 
                    $size, 
                    $this->allocate( $color ), 
                    1, 
                    $position->x + 
                        ( $rotation === null ? 0 : $rotation->get( 0, 2 ) ),
                    $position->y + 
                        ( $rotation === null ? 0 : $rotation->get( 1, 2 ) ),
                    0,
                    0,
                    ( $rotation === null ? 0 : -$rotation->getRotation() ),
                    4
                );
                break;
            case ezcGraph::TTF_FONT:
                switch ( true )
                {
                    case ezcBaseFeatures::hasFunction( 'imagefttext' ) && !$this->options->forceNativeTTF:
                        imageFtText(
                            $image, 
                            $size,
                            ( $rotation === null ? 0 : -$rotation->getRotation() ),
                            $position->x + 
                                ( $rotation === null ? 0 : $rotation->get( 0, 2 ) ),
                            $position->y + 
                                ( $rotation === null ? 0 : $rotation->get( 1, 2 ) ),
                            $this->allocate( $color ),
                            $path,
                            $text
                        );
                        break;
                    case ezcBaseFeatures::hasFunction( 'imagettftext' ):
                        imageTtfText(
                            $image, 
                            $size,
                            ( $rotation === null ? 0 : -$rotation->getRotation() ),
                            $position->x + 
                                ( $rotation === null ? 0 : $rotation->get( 0, 2 ) ),
                            $position->y + 
                                ( $rotation === null ? 0 : $rotation->get( 1, 2 ) ),
                            $this->allocate( $color ),
                            $path,
                            $text
                        );
                        break;
                }
                break;
        }
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
    public function drawTextBox( $string, ezcGraphCoordinate $position, $width, $height, $align, ezcGraphRotation $rotation = null )
    {
        $padding = $this->options->font->padding + ( $this->options->font->border !== false ? $this->options->font->borderWidth : 0 );

        $width -= $padding * 2;
        $height -= $padding * 2;
        $position->x += $padding;
        $position->y += $padding;

        // Try to get a font size for the text to fit into the box
        $maxSize = min( $height, $this->options->font->maxFontSize );
        $result = false;
        for ( $size = $maxSize; $size >= $this->options->font->minFontSize; --$size )
        {
            $result = $this->testFitStringInTextBox( $string, $position, $width, $height, $size );
            if ( is_array( $result ) )
            {
                break;
            }
            $size = floor( ( $newsize = $size * ( $result ) ) >= $size ? $size - 1 : $newsize );
        }

        if ( !is_array( $result ) )
        {
            if ( ( $height >= $this->options->font->minFontSize ) &&
                 ( $this->options->autoShortenString ) )
            {
                $result = $this->tryFitShortenedString( $string, $position, $width, $height, $size = $this->options->font->minFontSize );
            } 
            else
            {
                throw new ezcGraphFontRenderingException( $string, $this->options->font->minFontSize, $width, $height );
            }
        }

        $this->options->font->minimalUsedFont = $size;

        $this->strings[] = array(
            'text' => $result,
            'position' => $position,
            'width' => $width,
            'height' => $height,
            'align' => $align,
            'font' => $this->options->font,
            'rotation' => $rotation,
        );

        return array(
            clone $position,
            new ezcGraphCoordinate( $position->x + $width, $position->y ),
            new ezcGraphCoordinate( $position->x + $width, $position->y + $height ),
            new ezcGraphCoordinate( $position->x, $position->y + $height ),
        );
    }
    
    /**
     * Draw all collected texts
     *
     * The texts are collected and their maximum possible font size is 
     * calculated. This function finally draws the texts on the image, this
     * delayed drawing has two reasons:
     *
     * 1) This way the text strings are always on top of the image, what 
     *    results in better readable texts
     * 2) The maximum possible font size can be calculated for a set of texts
     *    with the same font configuration. Strings belonging to one chart 
     *    element normally have the same font configuration, so that all texts
     *    belonging to one element will have the same font size.
     * 
     * @access protected
     * @return void
     */
    protected function drawAllTexts()
    {
        $image = $this->getImage();

        foreach ( $this->strings as $text )
        {
            $size = $text['font']->minimalUsedFont;

            $completeHeight = count( $text['text'] ) * $size + ( count( $text['text'] ) - 1 ) * $this->options->lineSpacing;

            // Calculate y offset for vertical alignement
            switch ( true )
            {
                case ( $text['align'] & ezcGraph::BOTTOM ):
                    $yOffset = $text['height'] - $completeHeight;
                    break;
                case ( $text['align'] & ezcGraph::MIDDLE ):
                    $yOffset = ( $text['height'] - $completeHeight ) / 2;
                    break;
                case ( $text['align'] & ezcGraph::TOP ):
                default:
                    $yOffset = 0;
                    break;
            }

            $padding = $text['font']->padding + $text['font']->borderWidth / 2;
            if ( $this->options->font->minimizeBorder === true )
            {
                // Calculate maximum width of text rows
                $width = false;
                foreach ( $text['text'] as $line )
                {
                    $string = implode( ' ', $line );
                    $boundings = $this->getTextBoundings( $size, $text['font'], $string );
                    if ( ( $width === false) || ( $boundings->width > $width ) )
                    {
                        $width = $boundings->width;
                    }
                }

                switch ( true )
                {
                    case ( $text['align'] & ezcGraph::LEFT ):
                        $xOffset = 0;
                        break;
                    case ( $text['align'] & ezcGraph::CENTER ):
                        $xOffset = ( $text['width'] - $width ) / 2;
                        break;
                    case ( $text['align'] & ezcGraph::RIGHT ):
                        $xOffset = $text['width'] - $width;
                        break;
                }

                $borderPolygonArray = array(
                    new ezcGraphCoordinate(
                        $text['position']->x - $padding + $xOffset,
                        $text['position']->y - $padding + $yOffset
                    ),
                    new ezcGraphCoordinate(
                        $text['position']->x + $padding * 2 + $xOffset + $width,
                        $text['position']->y - $padding + $yOffset
                    ),
                    new ezcGraphCoordinate(
                        $text['position']->x + $padding * 2 + $xOffset + $width,
                        $text['position']->y + $padding * 2 + $yOffset + $completeHeight
                    ),
                    new ezcGraphCoordinate(
                        $text['position']->x - $padding + $xOffset,
                        $text['position']->y + $padding * 2 + $yOffset + $completeHeight
                    ),
                );
            }
            else
            {
                $borderPolygonArray = array(
                    new ezcGraphCoordinate(
                        $text['position']->x - $padding,
                        $text['position']->y - $padding
                    ),
                    new ezcGraphCoordinate(
                        $text['position']->x + $padding * 2 + $text['width'],
                        $text['position']->y - $padding
                    ),
                    new ezcGraphCoordinate(
                        $text['position']->x + $padding * 2 + $text['width'],
                        $text['position']->y + $padding * 2 + $text['height']
                    ),
                    new ezcGraphCoordinate(
                        $text['position']->x - $padding,
                        $text['position']->y + $padding * 2 + $text['height']
                    ),
                );
            }

            if ( $text['rotation'] !==  null )
            {
                foreach ( $borderPolygonArray as $nr => $point )
                {
                    $borderPolygonArray[$nr] = $text['rotation']->transformCoordinate( $point );
                }
            }

            if ( $text['font']->background !== false )
            {
                $this->drawPolygon( 
                    $borderPolygonArray, 
                    $text['font']->background,
                    true
                );
            }

            if ( $text['font']->border !== false )
            {
                $this->drawPolygon( 
                    $borderPolygonArray, 
                    $text['font']->border,
                    false,
                    $text['font']->borderWidth
                );
            }

            // Render text with evaluated font size
            foreach ( $text['text'] as $line )
            {
                $string = implode( ' ', $line );
                $boundings = $this->getTextBoundings( $size, $text['font'], $string );
                $text['position']->y += $size;

                switch ( true )
                {
                    case ( $text['align'] & ezcGraph::LEFT ):
                        $position = new ezcGraphCoordinate( 
                            $text['position']->x, 
                            $text['position']->y + $yOffset
                        );
                        break;
                    case ( $text['align'] & ezcGraph::RIGHT ):
                        $position = new ezcGraphCoordinate( 
                            $text['position']->x + ( $text['width'] - $boundings->width ), 
                            $text['position']->y + $yOffset
                        );
                        break;
                    case ( $text['align'] & ezcGraph::CENTER ):
                        $position = new ezcGraphCoordinate( 
                            $text['position']->x + ( ( $text['width'] - $boundings->width ) / 2 ), 
                            $text['position']->y + $yOffset
                        );
                        break;
                }

                // Calculate relative modification of rotation center point
                if ( $text['rotation'] !== null )
                {
                    $rotation = new ezcGraphRotation(
                        $text['rotation']->getRotation(),
                        new ezcGraphCoordinate(
                            $text['rotation']->getCenter()->x + 
                                $position->x - $text['position']->x,
                            $text['rotation']->getCenter()->y + 
                                $position->y - $text['position']->y
                        )
                    );
                    $rotation = $text['rotation'];
                }
                else
                {
                    $rotation = null;
                }

                // Optionally draw text shadow
                if ( $text['font']->textShadow === true )
                {
                    $this->renderText( 
                        $image, 
                        $string,
                        $text['font']->type, 
                        $text['font']->path, 
                        $text['font']->textShadowColor,
                        new ezcGraphCoordinate(
                            $position->x + $text['font']->textShadowOffset,
                            $position->y + $text['font']->textShadowOffset
                        ),
                        $size,
                        $rotation
                    );
                }
                
                // Finally draw text
                $this->renderText( 
                    $image, 
                    $string,
                    $text['font']->type, 
                    $text['font']->path, 
                    $text['font']->color, 
                    $position,
                    $size,
                    $rotation
                );

                $text['position']->y += $size * $this->options->lineSpacing;
            }
        }
    }

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
    public function drawCircleSector( ezcGraphCoordinate $center, $width, $height, $startAngle, $endAngle, ezcGraphColor $color, $filled = true )
    {
        $image = $this->getImage();
        $drawColor = $this->allocate( $color );

        // Normalize angles
        if ( $startAngle > $endAngle )
        {
            $tmp = $startAngle;
            $startAngle = $endAngle;
            $endAngle = $tmp;
        }

        if ( ( $endAngle - $startAngle ) > 359.99999 )
        {
            return $this->drawCircle( $center, $width, $height, $color, $filled );
        }

        // Because of bug #45552 in PHPs ext/GD we check for a minimal distance
        // on the outer border of the circle sector, and skip the drawing if
        // the distance is lower then 1.
        //
        // See also: http://bugs.php.net/45552
        $startPoint = new ezcGraphVector( 
            $center->x + 
                ( ( cos( deg2rad( $startAngle ) ) * $width ) / 2 ),
            $center->y + 
                ( ( sin( deg2rad( $startAngle ) ) * $height ) / 2 )
        );
        if ( $startPoint->sub( new ezcGraphVector( 
                $center->x + 
                    ( ( cos( deg2rad( $endAngle ) ) * $width ) / 2 ),
                $center->y + 
                    ( ( sin( deg2rad( $endAngle ) ) * $height ) / 2 )
             ) )->length() < 1 )
        {
            // Skip this circle sector
            return array();
        }

        if ( $filled )
        {
            imagefilledarc( 
                $image, 
                $this->supersample( $center->x ), 
                $this->supersample( $center->y ), 
                $this->supersample( $width ), 
                $this->supersample( $height ), 
                $startAngle, 
                $endAngle, 
                $drawColor, 
                IMG_ARC_PIE 
            );
        }
        else
        {
            imagefilledarc( 
                $image, 
                $this->supersample( $center->x ), 
                $this->supersample( $center->y ), 
                $this->supersample( $width ), 
                $this->supersample( $height ), 
                $startAngle, 
                $endAngle, 
                $drawColor, 
                IMG_ARC_PIE | IMG_ARC_NOFILL | IMG_ARC_EDGED
            );
        }

        // Create polygon array to return
        $polygonArray = array( $center );
        for ( $angle = $startAngle; $angle < $endAngle; $angle += $this->options->imageMapResolution )
        {
            $polygonArray[] = new ezcGraphCoordinate(
                $center->x + 
                    ( ( cos( deg2rad( $angle ) ) * $width ) / 2 ),
                $center->y + 
                    ( ( sin( deg2rad( $angle ) ) * $height ) / 2 )
            );
        }
        $polygonArray[] = new ezcGraphCoordinate(
            $center->x + 
                ( ( cos( deg2rad( $endAngle ) ) * $width ) / 2 ),
            $center->y + 
                ( ( sin( deg2rad( $endAngle ) ) * $height ) / 2 )
        );

        return $polygonArray;
    }

    /**
     * Draws a single element of a circular arc
     * 
     * ext/gd itself does not support something like circular arcs, so that
     * this functions draws rectangular polygons as a part of circular arcs
     * to interpolate them. This way it is possible to apply a linear gradient
     * to the circular arc, because we draw single steps anyway.
     *
     * @param ezcGraphCoordinate $center Center of ellipse
     * @param integer $width Width of ellipse
     * @param integer $height Height of ellipse
     * @param integer $size Height of border
     * @param float $startAngle Starting angle of circle sector
     * @param float $endAngle Ending angle of circle sector
     * @param ezcGraphColor $color Color of Border
     * @return void
     */
    protected function drawCircularArcStep( ezcGraphCoordinate $center, $width, $height, $size, $startAngle, $endAngle, ezcGraphColor $color )
    {
        $this->drawPolygon(
            array(
                new ezcGraphCoordinate(
                    $center->x + 
                        ( ( cos( deg2rad( $startAngle ) ) * $width ) / 2 ),
                    $center->y + 
                        ( ( sin( deg2rad( $startAngle ) ) * $height ) / 2 )
                ),
                new ezcGraphCoordinate(
                    $center->x + 
                        ( ( cos( deg2rad( $startAngle ) ) * $width ) / 2 ),
                    $center->y + 
                        ( ( sin( deg2rad( $startAngle ) ) * $height ) / 2 ) + $size
                ),
                new ezcGraphCoordinate(
                    $center->x + 
                        ( ( cos( deg2rad( $endAngle ) ) * $width ) / 2 ),
                    $center->y + 
                        ( ( sin( deg2rad( $endAngle ) ) * $height ) / 2 ) + $size
                ),
                new ezcGraphCoordinate(
                    $center->x + 
                        ( ( cos( deg2rad( $endAngle ) ) * $width ) / 2 ),
                    $center->y + 
                        ( ( sin( deg2rad( $endAngle ) ) * $height ) / 2 )
                ),
            ),
            $color->darken( $this->options->shadeCircularArc * ( 1 + cos ( deg2rad( $startAngle ) ) ) / 2 ),
            true
        );
    }
 
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
     * @param bool $filled
     * @return void
     */
    public function drawCircularArc( ezcGraphCoordinate $center, $width, $height, $size, $startAngle, $endAngle, ezcGraphColor $color, $filled = true )
    {
        $image = $this->getImage();
        $drawColor = $this->allocate( $color );

        // Normalize angles
        if ( $startAngle > $endAngle )
        {
            $tmp = $startAngle;
            $startAngle = $endAngle;
            $endAngle = $tmp;
        }
 
        if ( $filled === true )
        {
            $startIteration = ceil( $startAngle / $this->options->detail ) * $this->options->detail;
            $endIteration = floor( $endAngle / $this->options->detail ) * $this->options->detail;

            if ( $startAngle < $startIteration )
            {
                // Draw initial step
                $this->drawCircularArcStep( 
                    $center, 
                    $width, 
                    $height, 
                    $size, 
                    $startAngle, 
                    $startIteration, 
                    $color
                );
            }

            // Draw all steps
            for ( ; $startIteration < $endIteration; $startIteration += $this->options->detail )
            {
                $this->drawCircularArcStep( 
                    $center, 
                    $width, 
                    $height, 
                    $size, 
                    $startIteration, 
                    $startIteration + $this->options->detail, 
                    $color 
                );
            }

            if ( $endIteration < $endAngle )
            {
                // Draw closing step
                $this->drawCircularArcStep( 
                    $center, 
                    $width, 
                    $height, 
                    $size, 
                    $endIteration, 
                    $endAngle, 
                    $color 
                );
            }
        }
        else
        {
            imagefilledarc( 
                $image, 
                $this->supersample( $center->x ), 
                $this->supersample( $center->y ), 
                $this->supersample( $width ), 
                $this->supersample( $height ), 
                $startAngle, 
                $endAngle, 
                $drawColor, 
                IMG_ARC_PIE | IMG_ARC_NOFILL
            );
        }

        // Create polygon array to return
        $polygonArray = array();
        for ( $angle = $startAngle; $angle < $endAngle; $angle += $this->options->imageMapResolution )
        {
            $polygonArray[] = new ezcGraphCoordinate(
                $center->x + 
                    ( ( cos( deg2rad( $angle ) ) * $width ) / 2 ),
                $center->y + 
                    ( ( sin( deg2rad( $angle ) ) * $height ) / 2 )
            );
        }
        $polygonArray[] = new ezcGraphCoordinate(
            $center->x + 
                ( ( cos( deg2rad( $endAngle ) ) * $width ) / 2 ),
            $center->y + 
                ( ( sin( deg2rad( $endAngle ) ) * $height ) / 2 )
        );

        for ( $angle = $endAngle; $angle > $startAngle; $angle -= $this->options->imageMapResolution )
        {
            $polygonArray[] = new ezcGraphCoordinate(
                $center->x + 
                    ( ( cos( deg2rad( $angle ) ) * $width ) / 2 ) + $size,
                $center->y + 
                    ( ( sin( deg2rad( $angle ) ) * $height ) / 2 )
            );
        }
        $polygonArray[] = new ezcGraphCoordinate(
            $center->x + 
                ( ( cos( deg2rad( $startAngle ) ) * $width ) / 2 ) + $size,
            $center->y + 
                ( ( sin( deg2rad( $startAngle ) ) * $height ) / 2 )
        );

        return $polygonArray;
    }
    
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
    public function drawCircle( ezcGraphCoordinate $center, $width, $height, ezcGraphColor $color, $filled = true )
    {
        $image = $this->getImage();

        $drawColor = $this->allocate( $color );

        if ( $filled )
        {
            imagefilledellipse( 
                $image, 
                $this->supersample( $center->x ), 
                $this->supersample( $center->y ), 
                $this->supersample( $width ), 
                $this->supersample( $height ), 
                $drawColor 
            );
        }
        else
        {
            imageellipse( 
                $image, 
                $this->supersample( $center->x ), 
                $this->supersample( $center->y ), 
                $this->supersample( $width ), 
                $this->supersample( $height ), 
                $drawColor 
            );
        }

        $polygonArray = array();
        for ( $angle = 0; $angle < 360; $angle += $this->options->imageMapResolution )
        {
            $polygonArray[] = new ezcGraphCoordinate(
                $center->x + 
                    ( ( cos( deg2rad( $angle ) ) * $width ) / 2 ),
                $center->y + 
                    ( ( sin( deg2rad( $angle ) ) * $height ) / 2 )
            );
        }

        return $polygonArray;
    }
    
    /**
     * Draw an image
     *
     * The actual drawing of the image is delayed, to not apply supersampling 
     * to the image. The image will normally be resized using the gd function
     * imagecopyresampled, which provides nice antialiased scaling, so that
     * additional supersampling would make the image look blurred. The delayed
     * images will be pre-processed, so that they are draw in the back of 
     * everything else.
     * 
     * @param mixed $file Image file
     * @param ezcGraphCoordinate $position Top left position
     * @param mixed $width Width of image in destination image
     * @param mixed $height Height of image in destination image
     * @return void
     */
    public function drawImage( $file, ezcGraphCoordinate $position, $width, $height )
    {
        $this->preProcessImages[] = array(
            'file' => $file, 
            'position' => clone $position,
            'width' => $width,
            'height' => $height,
        );

        return array(
            $position,
            new ezcGraphCoordinate( $position->x + $width, $position->y ),
            new ezcGraphCoordinate( $position->x + $width, $position->y + $height ),
            new ezcGraphCoordinate( $position->x, $position->y + $height ),
        );
    }

    /**
     * Draw all images to image resource handler
     * 
     * @param resource $image Image to draw on
     * @return resource Updated image resource
     */
    protected function addImages( $image )
    {
        foreach ( $this->preProcessImages as $preImage )
        {
            $preImageData = $this->imageCreateFrom( $preImage['file'] );
            call_user_func_array(
                $this->options->resampleFunction,
                array(
                    $image,
                    $preImageData['image'],
                    $preImage['position']->x, $preImage['position']->y,
                    0, 0,
                    $preImage['width'], $preImage['height'],
                    $preImageData['width'], $preImageData['height'],
                )
            );
        }

        return $image;
    }

    /**
     * Return mime type for current image format
     * 
     * @return string
     */
    public function getMimeType()
    {
        switch ( $this->options->imageFormat )
        {
            case IMG_PNG:
                return 'image/png';
            case IMG_JPEG:
                return 'image/jpeg';
        }
    }

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
        $this->render( null );
    }

    /**
     * Finally save image
     * 
     * @param string $file Destination filename
     * @return void
     */
    public function render( $file )
    {
        $destination = imagecreatetruecolor( $this->options->width, $this->options->height );

        // Default to a transparent white background
        $bgColor = imagecolorallocatealpha( $destination, 255, 255, 255, 127 );
        imagealphablending( $destination, true );
        imagesavealpha( $destination, true );
        imagefill( $destination, 1, 1, $bgColor );

        // Apply background if one is defined
        if ( $this->options->background !== false )
        {
            $background = $this->imageCreateFrom( $this->options->background );

            call_user_func_array(
                $this->options->resampleFunction,
                array(
                    $destination,
                    $background['image'],
                    0, 0,
                    0, 0,
                    $this->options->width, $this->options->height,
                    $background['width'], $background['height'],
                )
            );
        }

        // Draw all images to exclude them from supersampling
        $destination = $this->addImages( $destination );

        // Finally merge with graph
        $image = $this->getImage();
        call_user_func_array(
            $this->options->resampleFunction,
            array(
                $destination,
                $image,
                0, 0,
                0, 0,
                $this->options->width, $this->options->height,
                $this->supersample( $this->options->width ), $this->supersample( $this->options->height )
            )
        );

        $this->image = $destination;
        imagedestroy( $image );

        // Draw all texts
        // Reset supersampling during text rendering
        $supersampling = $this->options->supersampling;
        $this->options->supersampling = 1;
        $this->drawAllTexts();
        $this->options->supersampling = $supersampling;

        $image = $this->getImage();
        switch ( $this->options->imageFormat )
        {
            case IMG_PNG:
                if ( $file === null )
                {
                    imagepng( $image );
                }
                else
                {
                    imagepng( $image, $file );
                }
                break;
            case IMG_JPEG:
                imagejpeg( $image, $file, $this->options->jpegQuality );
                break;
            default:
                throw new ezcGraphGdDriverUnsupportedImageTypeException( $this->options->imageFormat );
        }
    }

    /**
     * Get resource of rendered result
     *
     * Return the resource of the rendered result. You should not use this
     * method before you called either renderToOutput() or render(), as the
     * image may not be completely rendered until then.
     * 
     * @return resource
     */
    public function getResource()
    {
        return $this->image;
    }
}

?>
